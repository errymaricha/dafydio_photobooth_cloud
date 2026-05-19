<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $payments = Payment::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        $templateIds = collect($payments->items())
            ->pluck('payload.cloud_template_id')
            ->filter()
            ->unique()
            ->values();

        $templates = CloudTemplate::query()
            ->where('tenant_id', $customer->tenant_id)
            ->whereIn('id', $templateIds)
            ->get()
            ->keyBy('id');

        return response()->json([
            'data' => collect($payments->items())->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'purpose' => $payment->purpose,
                'template_name' => $templates->get($payment->payload['cloud_template_id'] ?? null)?->name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'provider' => $payment->provider,
                'manual_instruction' => $payment->status === 'pending'
                    ? 'Transfer manual/QRIS lalu kirim bukti pembayaran ke admin Dafydio Photobooth.'
                    : null,
                'created_at' => $payment->created_at?->toDateTimeString(),
            ])->values(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
            'message' => null,
        ]);
    }
}
