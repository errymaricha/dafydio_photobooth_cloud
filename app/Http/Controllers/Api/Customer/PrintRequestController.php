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
            'status' => 'pending',
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
}
