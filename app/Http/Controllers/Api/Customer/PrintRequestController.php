<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudPrintRequest;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $printRequests = CloudPrintRequest::query()
            ->with([
                'asset:id,type,station_asset_id',
                'session:id,title,station_session_id,metadata',
                'station:id,name,code',
            ])
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($printRequests->items())
                ->map(fn (CloudPrintRequest $printRequest): array => $this->printRequestPayload($printRequest))
                ->values(),
            'meta' => [
                'current_page' => $printRequests->currentPage(),
                'last_page' => $printRequests->lastPage(),
                'total' => $printRequests->total(),
            ],
            'message' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user();

        $data = $request->validate([
            'cloud_session_id' => ['required', 'exists:cloud_sessions,id'],
            'cloud_session_asset_id' => ['required', 'exists:cloud_session_assets,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $session = CloudSession::query()->findOrFail($data['cloud_session_id']);
        $asset = CloudSessionAsset::query()->findOrFail($data['cloud_session_asset_id']);

        abort_unless($session->tenant_id === $customer->tenant_id, 404);
        abort_unless($session->customer_id === $customer->id, 404);
        abort_unless($asset->cloud_session_id === $session->id, 422);

        $printRequest = CloudPrintRequest::query()->create([
            'tenant_id' => $customer->tenant_id,
            'station_id' => $session->station_id,
            'customer_id' => $customer->id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => $data['quantity'],
            'status' => 'pending_operator',
            'priority' => 'normal',
            'payment_status' => 'not_required',
        ]);

        return response()->json([
            'data' => [
                'print_request_id' => $printRequest->id,
                'status' => $printRequest->status,
                'payment_status' => $printRequest->payment_status,
            ],
            'meta' => [],
            'message' => 'Print request created',
        ], 201);
    }

    private function printRequestPayload(CloudPrintRequest $printRequest): array
    {
        $sessionCode = $printRequest->session?->metadata['station_session']['session_code']
            ?? $printRequest->session?->station_session_id;

        return [
            'id' => $printRequest->id,
            'cloud_session_id' => $printRequest->cloud_session_id,
            'cloud_session_asset_id' => $printRequest->cloud_session_asset_id,
            'session_title' => $printRequest->session?->title,
            'session_code' => $sessionCode,
            'asset_type' => $printRequest->asset?->type,
            'station_asset_id' => $printRequest->asset?->station_asset_id,
            'station_name' => $printRequest->station?->name,
            'station_code' => $printRequest->station?->code,
            'quantity' => $printRequest->quantity,
            'status' => $printRequest->status,
            'priority' => $printRequest->priority,
            'payment_status' => $printRequest->payment_status,
            'station_print_order_id' => $printRequest->station_print_order_id,
            'station_print_queue_job_id' => $printRequest->station_print_queue_job_id,
            'claimed_at' => $printRequest->claimed_at?->toDateTimeString(),
            'printed_at' => $printRequest->printed_at?->toDateTimeString(),
            'failed_at' => $printRequest->failed_at?->toDateTimeString(),
            'last_error' => $printRequest->last_error,
            'created_at' => $printRequest->created_at?->toDateTimeString(),
        ];
    }
}
