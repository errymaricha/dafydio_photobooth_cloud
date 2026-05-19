<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use App\Models\CloudPrintRequest;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrintRequestController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function index(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');
        $limit = min((int) $request->integer('limit', 10), 50);

        $requests = CloudPrintRequest::query()
            ->with(['asset', 'tenant'])
            ->where('tenant_id', $station->tenant_id)
            ->where('station_id', $station->id)
            ->where('status', $request->query('status', 'pending'))
            ->oldest()
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $requests->map(fn (CloudPrintRequest $printRequest): array => [
                'print_request_id' => $printRequest->id,
                'cloud_session_id' => $printRequest->cloud_session_id,
                'asset_id' => $printRequest->cloud_session_asset_id,
                'asset_download_url' => $printRequest->asset
                    ? $this->assetUrlService->downloadUrl($printRequest->asset, now()->addMinutes(10))
                    : null,
                'quantity' => $printRequest->quantity,
                'priority' => $printRequest->priority,
                'created_at' => $printRequest->created_at?->toISOString(),
            ]),
            'meta' => [],
            'message' => null,
        ]);
    }

    public function update(Request $request, CloudPrintRequest $printRequest): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($printRequest->tenant_id === $station->tenant_id, 404);
        abort_unless($printRequest->station_id === $station->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['claimed', 'printing', 'printed', 'failed'])],
            'error_message' => ['nullable', 'string'],
        ]);

        $printRequest->update([
            'status' => $data['status'],
            'station_claimed_at' => $data['status'] === 'claimed' ? now() : $printRequest->station_claimed_at,
            'printed_at' => $data['status'] === 'printed' ? now() : $printRequest->printed_at,
            'metadata' => [
                ...($printRequest->metadata ?? []),
                'station_error_message' => $data['error_message'] ?? null,
            ],
        ]);

        return response()->json([
            'data' => [
                'print_request_id' => $printRequest->id,
                'status' => $printRequest->status,
            ],
            'meta' => [],
            'message' => 'Print request updated',
        ]);
    }
}
