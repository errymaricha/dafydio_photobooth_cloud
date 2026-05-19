<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $plan = $request->query('plan', 'all');
        $plan = in_array($plan, ['all', 'regular', 'premium'], true) ? $plan : 'all';

        $customers = Customer::query()
            ->withCount(['sessions', 'printRequests'])
            ->with(['subscriptions' => fn ($query) => $query->latest()->limit(1)])
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('name', 'like', $like)
                        ->orWhere('whatsapp_number', 'like', $like)
                        ->orWhereHas('subscriptions', function ($query) use ($like): void {
                            $query->where('plan', 'like', $like);
                        });
                });
            })
            ->when($plan !== 'all', function ($query) use ($plan): void {
                $query->whereHas('subscriptions', function ($query) use ($plan): void {
                    $query->where('plan', $plan);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers->through(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'whatsapp_number' => $customer->whatsapp_number,
                'status' => $customer->status,
                'last_login_at' => $customer->last_login_at?->diffForHumans(),
                'created_at' => $customer->created_at?->diffForHumans(),
                'sessions_count' => $customer->sessions_count,
                'print_requests_count' => $customer->print_requests_count,
                'subscription_plan' => $customer->subscriptions->first()?->plan ?? 'regular',
            ]),
            'filters' => [
                'q' => $search,
                'plan' => $plan,
            ],
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 404);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update([
            'name' => filled($data['name'] ?? null) ? trim($data['name']) : null,
        ]);

        return back()->with('status', 'Nama customer berhasil diperbarui.');
    }

    public function show(Request $request, Customer $customer): Response
    {
        abort_unless($customer->tenant_id === $request->user()->tenant_id, 404);

        $customer->load(['subscriptions' => fn ($query) => $query->latest()]);

        $sessions = $customer->sessions()
            ->with(['station'])
            ->withCount('assets')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'whatsapp_number' => $customer->whatsapp_number,
                'status' => $customer->status,
                'last_login_at' => $customer->last_login_at?->toDateTimeString(),
                'created_at' => $customer->created_at?->toDateTimeString(),
                'subscription_plan' => $customer->subscriptions->first()?->plan ?? 'regular',
                'subscription_status' => $customer->subscriptions->first()?->status ?? 'active',
            ],
            'sessions' => $sessions->through(fn ($session): array => [
                'id' => $session->id,
                'title' => $session->title ?: 'Untitled Session',
                'station_session_id' => $session->station_session_id,
                'public_session_code' => $session->metadata['station_session']['session_code'] ?? $session->station_session_id,
                'public_url' => route('public.sessions.show', [
                    'sessionCode' => $session->metadata['station_session']['session_code'] ?? $session->station_session_id,
                ]),
                'station_name' => $session->station?->name,
                'sync_status' => $session->sync_status,
                'assets_count' => $session->assets_count,
                'created_at' => $session->created_at?->diffForHumans(),
            ]),
        ]);
    }
}
