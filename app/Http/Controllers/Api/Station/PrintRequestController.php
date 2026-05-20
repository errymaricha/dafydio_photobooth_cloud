<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use App\Models\CloudPrintRequest;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrintRequestController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    private const READY_STATUS_ALIASES = [
        'pending' => ['pending', 'pending_operator'],
        'pending_operator' => ['pending_operator'],
    ];

    private const ALLOWED_TRANSITIONS = [
        'pending' => ['claimed', 'failed'],
        'pending_operator' => ['claimed', 'failed'],
        'claimed' => ['claimed', 'printing', 'failed'],
        'printing' => ['printing', 'printed', 'failed'],
        'printed' => ['printed'],
        'failed' => ['failed'],
    ];

    public function index(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');
        $limit = min(max((int) $request->integer('limit', 25), 1), 50);
        $status = (string) $request->query('status', 'pending');
        $statuses = self::READY_STATUS_ALIASES[$status] ?? [$status];

        $requests = CloudPrintRequest::query()
            ->with(['asset', 'session:id,station_session_id,metadata'])
            ->where('tenant_id', $station->tenant_id)
            ->where('station_id', $station->id)
            ->whereIn('status', $statuses)
            ->whereIn('payment_status', ['paid', 'not_required'])
            ->whereNull('claimed_at')
            ->oldest('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => [
                'print_requests' => $requests->map(fn (CloudPrintRequest $printRequest): array => $this->stationPayload($printRequest))->values(),
            ],
            'meta' => [
                'limit' => $limit,
                'status' => $status,
            ],
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
            'station_id' => ['nullable', 'string', 'max:255'],
            'station_print_order_id' => ['nullable', 'string', 'max:255'],
            'station_print_queue_job_id' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'error_message' => ['nullable', 'string'],
        ]);

        $printRequest = DB::transaction(function () use ($data, $printRequest): CloudPrintRequest {
            /** @var CloudPrintRequest $locked */
            $locked = CloudPrintRequest::query()
                ->whereKey($printRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertAllowedTransition($locked, $data);

            $now = now();
            $message = $data['message'] ?? $data['error_message'] ?? null;
            $metadata = $locked->metadata ?? [];

            if (array_key_exists('message', $data) || array_key_exists('error_message', $data)) {
                $metadata['station_message'] = $message;
            }

            $locked->fill([
                'status' => $data['status'],
                'station_local_id' => $data['station_id'] ?? $locked->station_local_id,
                'station_print_order_id' => $data['station_print_order_id'] ?? $locked->station_print_order_id,
                'station_print_queue_job_id' => $data['station_print_queue_job_id'] ?? $locked->station_print_queue_job_id,
                'claimed_at' => $data['status'] === 'claimed' ? ($locked->claimed_at ?? $now) : $locked->claimed_at,
                'station_claimed_at' => $data['status'] === 'claimed' ? ($locked->station_claimed_at ?? $now) : $locked->station_claimed_at,
                'printed_at' => $data['status'] === 'printed' ? ($locked->printed_at ?? $now) : $locked->printed_at,
                'failed_at' => $data['status'] === 'failed' ? ($locked->failed_at ?? $now) : $locked->failed_at,
                'last_error' => $data['status'] === 'failed' ? $message : $locked->last_error,
                'metadata' => $metadata,
            ])->save();

            return $locked->refresh();
        });

        return response()->json([
            'data' => [
                'print_request_id' => $printRequest->id,
                'id' => $printRequest->id,
                'status' => $printRequest->status,
                'station_print_order_id' => $printRequest->station_print_order_id,
                'station_print_queue_job_id' => $printRequest->station_print_queue_job_id,
                'claimed_at' => $printRequest->claimed_at?->toISOString(),
                'printed_at' => $printRequest->printed_at?->toISOString(),
                'failed_at' => $printRequest->failed_at?->toISOString(),
            ],
            'meta' => [],
            'message' => 'Print request updated',
        ]);
    }

    private function stationPayload(CloudPrintRequest $printRequest): array
    {
        $sessionCode = $printRequest->session?->metadata['station_session']['session_code']
            ?? $printRequest->session?->station_session_id;

        return [
            'id' => $printRequest->id,
            'print_request_id' => $printRequest->id,
            'cloud_session_id' => $printRequest->cloud_session_id,
            'cloud_session_asset_id' => $printRequest->cloud_session_asset_id,
            'asset_id' => $printRequest->cloud_session_asset_id,
            'asset_download_url' => $printRequest->asset
                ? $this->assetUrlService->downloadUrl($printRequest->asset, now()->addMinutes(10))
                : null,
            'station_session_id' => $printRequest->session?->station_session_id,
            'session_code' => $sessionCode,
            'copies' => $printRequest->quantity,
            'quantity' => $printRequest->quantity,
            'paper_size' => $printRequest->metadata['paper_size'] ?? null,
            'priority' => $printRequest->priority,
            'payment_status' => $printRequest->payment_status,
            'created_at' => $printRequest->created_at?->toISOString(),
        ];
    }

    private function assertAllowedTransition(CloudPrintRequest $printRequest, array $data): void
    {
        $nextStatus = $data['status'];
        $currentStatus = $printRequest->status;
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        abort_unless(in_array($nextStatus, $allowed, true), 422, "Cannot update print request from {$currentStatus} to {$nextStatus}.");

        if ($nextStatus !== 'claimed') {
            return;
        }

        $incomingOrderId = $data['station_print_order_id'] ?? null;
        $incomingQueueJobId = $data['station_print_queue_job_id'] ?? null;

        if (
            $printRequest->status === 'claimed'
            && $printRequest->station_print_order_id
            && $incomingOrderId
            && $printRequest->station_print_order_id !== $incomingOrderId
        ) {
            abort(409, 'Print request already claimed by another station print order.');
        }

        if (
            $printRequest->status === 'claimed'
            && $printRequest->station_print_queue_job_id
            && $incomingQueueJobId
            && $printRequest->station_print_queue_job_id !== $incomingQueueJobId
        ) {
            abort(409, 'Print request already claimed by another station queue job.');
        }
    }
}
