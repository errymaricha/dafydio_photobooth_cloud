<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use App\Models\CustomerTemplateEntitlement;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $status = in_array($status, ['all', 'pending', 'paid', 'failed', 'expired', 'refunded'], true) ? $status : 'all';

        $payments = Payment::query()
            ->with('customer:id,name,whatsapp_number')
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('id', 'like', $like)
                        ->orWhere('purpose', 'like', $like)
                        ->orWhere('payload', 'like', $like)
                        ->orWhereHas('customer', function ($query) use ($like): void {
                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('whatsapp_number', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $templateIds = collect($payments->items())
            ->pluck('payload.cloud_template_id')
            ->filter()
            ->unique()
            ->values();

        $templates = CloudTemplate::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->whereIn('id', $templateIds)
            ->get()
            ->keyBy('id');

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments->through(fn (Payment $payment): array => [
                'id' => $payment->id,
                'customer_name' => $payment->customer?->name,
                'customer_whatsapp' => $payment->customer?->whatsapp_number,
                'purpose' => $payment->purpose,
                'template_name' => $templates->get($payment->payload['cloud_template_id'] ?? null)?->name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'provider' => $payment->provider,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'created_at' => $payment->created_at?->toDateTimeString(),
            ]),
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->tenant_id === $request->user()->tenant_id, 404);
        abort_unless($payment->status === 'pending', 422);

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        if ($payment->purpose === 'template_purchase' && filled($payment->payload['cloud_template_id'] ?? null)) {
            CustomerTemplateEntitlement::query()->updateOrCreate(
                [
                    'tenant_id' => $payment->tenant_id,
                    'customer_id' => $payment->customer_id,
                    'cloud_template_id' => $payment->payload['cloud_template_id'],
                ],
                [
                    'source' => 'purchase',
                    'payment_id' => $payment->id,
                    'granted_at' => now(),
                ],
            );
        }

        return back()->with('success', 'Payment approved.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->tenant_id === $request->user()->tenant_id, 404);
        abort_unless($payment->status === 'pending', 422);

        $payment->update(['status' => 'failed']);

        return back()->with('success', 'Payment rejected.');
    }
}
