<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudSession;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function index(Request $request): Response
    {
        $identity = $request->query('identity', 'all');
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');
        $identity = in_array($identity, ['all', 'customers', 'guests'], true) ? $identity : 'all';
        $status = in_array($status, ['all', 'pending', 'syncing', 'complete', 'failed'], true) ? $status : 'all';

        $sessions = CloudSession::query()
            ->with(['customer', 'station'])
            ->withCount('assets')
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($identity === 'customers', fn ($query) => $query->whereNotNull('customer_id'))
            ->when($identity === 'guests', fn ($query) => $query->whereNull('customer_id'))
            ->when($status !== 'all', fn ($query) => $query->where('sync_status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('title', 'like', $like)
                        ->orWhere('station_session_id', 'like', $like)
                        ->orWhere('metadata', 'like', $like)
                        ->orWhereHas('customer', function ($query) use ($like): void {
                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('whatsapp_number', 'like', $like);
                        })
                        ->orWhereHas('station', function ($query) use ($like): void {
                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Sessions/Index', [
            'sessions' => $sessions->through(fn (CloudSession $session): array => [
                'id' => $session->id,
                'title' => $session->title ?: 'Untitled Session',
                'station_session_id' => $session->station_session_id,
                'public_session_code' => $this->sessionCode($session),
                'customer_name' => $this->customerLabel($session),
                'is_guest' => $session->customer_id === null,
                'station_name' => $session->station?->name,
                'sync_status' => $session->sync_status,
                'assets_count' => $session->assets_count,
                'created_at' => $session->created_at?->diffForHumans(),
            ]),
            'filters' => [
                'identity' => $identity,
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(Request $request, CloudSession $session): Response
    {
        abort_unless($session->tenant_id === $request->user()->tenant_id, 404);

        $session->load(['customer', 'station', 'assets']);

        return Inertia::render('Admin/Sessions/Show', [
            'session' => [
                'id' => $session->id,
                'title' => $session->title ?: 'Untitled Session',
                'station_session_id' => $session->station_session_id,
                'sync_status' => $session->sync_status,
                'started_at' => $session->started_at?->toDateTimeString(),
                'ended_at' => $session->ended_at?->toDateTimeString(),
                'created_at' => $session->created_at?->toDateTimeString(),
                'metadata' => $session->metadata,
            ],
            'customer' => $session->customer ? [
                'id' => $session->customer->id,
                'name' => $session->customer->name,
                'whatsapp_number' => $session->customer->whatsapp_number,
                'status' => $session->customer->status,
                'last_login_at' => $session->customer->last_login_at?->toDateTimeString(),
            ] : null,
            'station' => $session->station ? [
                'id' => $session->station->id,
                'name' => $session->station->name,
                'code' => $session->station->code,
                'status' => $session->station->status,
                'last_seen_at' => $session->station->last_seen_at?->toDateTimeString(),
            ] : null,
            'identityLabel' => $this->customerLabel($session),
            'publicSessionCode' => $this->sessionCode($session),
            'publicGalleryUrl' => route('public.sessions.show', ['sessionCode' => $this->sessionCode($session)]),
            'backToCustomerUrl' => $request->query('from_customer')
                && $session->customer_id
                    ? url("/admin/customers/{$session->customer_id}")
                : null,
            'assets' => $session->assets
                ->sortByDesc('created_at')
                ->values()
                ->map(fn ($asset): array => [
                    'id' => $asset->id,
                    'station_asset_id' => $asset->station_asset_id,
                    'type' => $asset->type,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                    'mime_type' => $asset->mime_type,
                    'size_bytes' => $asset->size_bytes,
                    'checksum' => $asset->checksum,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'status' => $asset->status,
                    'file_url' => $asset->status === 'uploaded'
                        ? $this->assetUrlService->downloadUrl($asset, now()->addMinutes(15))
                        : null,
                    'created_at' => $asset->created_at?->toDateTimeString(),
                ]),
        ]);
    }

    public function linkCustomer(Request $request, CloudSession $session): RedirectResponse
    {
        abort_unless($session->tenant_id === $request->user()->tenant_id, 404);

        $data = $request->validate([
            'customer_whatsapp' => ['required', 'string', 'max:32'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_tier' => ['nullable', 'in:regular,premium'],
        ]);

        $customer = Customer::query()->firstOrNew([
            'tenant_id' => $session->tenant_id,
            'whatsapp_number' => $data['customer_whatsapp'],
        ]);

        $customer->fill([
            'name' => $data['customer_name'] ?? $customer->name ?? 'Customer '.$data['customer_whatsapp'],
            'status' => 'active',
        ]);

        if (! $customer->exists) {
            $customer->password = Hash::make(str()->password(16));
        }

        $customer->save();

        CustomerSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $session->tenant_id,
                'customer_id' => $customer->id,
            ],
            [
                'plan' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 'premium' : 'regular',
                'status' => 'active',
                'starts_at' => now(),
                'print_quota' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 10 : 0,
                'storage_retention_days' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 365 : 30,
            ],
        );

        $metadata = $session->metadata ?? [];
        $metadata['station_session'] = [
            ...($metadata['station_session'] ?? []),
            'customer_whatsapp' => $data['customer_whatsapp'],
            'customer_tier' => $data['customer_tier'] ?? ($metadata['station_session']['customer_tier'] ?? 'regular'),
        ];
        $metadata['is_guest'] = false;

        $session->update([
            'customer_id' => $customer->id,
            'metadata' => $metadata,
        ]);

        return redirect()
            ->route('admin.sessions.show', $session)
            ->with('success', 'Guest session berhasil dihubungkan ke customer.');
    }

    private function sessionCode(CloudSession $session): string
    {
        return $session->metadata['station_session']['session_code'] ?? $session->station_session_id;
    }

    private function customerLabel(CloudSession $session): string
    {
        if ($session->customer) {
            return $session->customer->name ?: $session->customer->whatsapp_number;
        }

        return 'Guest - '.$this->sessionCode($session);
    }
}
