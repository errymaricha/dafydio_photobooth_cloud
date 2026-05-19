<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $sessions = CloudSession::query()
            ->with('assets')
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($sessions->items())->map(fn (CloudSession $session): array => $this->sessionPayload($session))->values(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'total' => $sessions->total(),
            ],
            'message' => null,
        ]);
    }

    public function show(Request $request, CloudSession $cloudSession): JsonResponse
    {
        $customer = $request->user();

        abort_unless($cloudSession->tenant_id === $customer->tenant_id, 404);
        abort_unless($cloudSession->customer_id === $customer->id, 404);

        return response()->json([
            'data' => $this->sessionPayload($cloudSession->load('assets')),
            'meta' => [],
            'message' => null,
        ]);
    }

    private function sessionPayload(CloudSession $session): array
    {
        $sessionCode = $session->metadata['station_session']['session_code'] ?? $session->station_session_id;

        return [
            'id' => $session->id,
            'tenant_id' => $session->tenant_id,
            'station_id' => $session->station_id,
            'customer_id' => $session->customer_id,
            'station_session_id' => $session->station_session_id,
            'session_code' => $sessionCode,
            'title' => $session->title,
            'started_at' => $session->started_at?->toDateTimeString(),
            'ended_at' => $session->ended_at?->toDateTimeString(),
            'created_at' => $session->created_at?->toDateTimeString(),
            'sync_status' => $session->sync_status,
            'public_url' => route('public.sessions.show', ['sessionCode' => $sessionCode]),
            'download_all_url' => route('public.sessions.download', ['sessionCode' => $sessionCode]),
            'assets' => $session->assets
                ->sortBy(fn (CloudSessionAsset $asset): int => $asset->type === 'framed' ? 0 : 1)
                ->values()
                ->map(fn (CloudSessionAsset $asset): array => [
                    'id' => $asset->id,
                    'station_asset_id' => $asset->station_asset_id,
                    'type' => $asset->type,
                    'status' => $asset->status,
                    'mime_type' => $asset->mime_type,
                    'size_bytes' => $asset->size_bytes,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'file_url' => $asset->status === 'uploaded'
                        ? $this->assetUrlService->downloadUrl($asset, now()->addMinutes(30))
                        : null,
                ]),
        ];
    }
}
