<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use App\Models\CustomerSubscription;
use App\Models\CustomerTemplateEntitlement;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $access = $request->query('access');
        $ownedTemplateIds = CustomerTemplateEntitlement::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('cloud_template_id')
            ->all();
        $hasPremium = CustomerSubscription::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where('plan', 'premium')
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->exists();

        $templates = CloudTemplate::query()
            ->where('tenant_id', $customer->tenant_id)
            ->when($access === 'owned', fn ($query) => $query->whereIn('id', $ownedTemplateIds))
            ->when($access && $access !== 'owned', fn ($query) => $query->where('access_level', $access))
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($templates->items())
                ->map(fn (CloudTemplate $template): array => $this->templatePayload($template, $ownedTemplateIds, $hasPremium))
                ->values(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'total' => $templates->total(),
            ],
            'message' => null,
        ]);
    }

    public function purchase(Request $request, CloudTemplate $template): JsonResponse
    {
        $customer = $request->user();

        abort_unless($template->tenant_id === $customer->tenant_id, 404);
        abort_unless($template->status === 'active' && $template->access_level === 'marketplace', 422);

        $existingEntitlement = CustomerTemplateEntitlement::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->where('cloud_template_id', $template->id)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($existingEntitlement) {
            return response()->json([
                'data' => [
                    'payment_id' => $existingEntitlement->payment_id,
                    'payment_url' => null,
                    'status' => 'owned',
                ],
                'meta' => [],
                'message' => 'Template already owned',
            ]);
        }

        $payment = Payment::query()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'provider' => 'manual',
            'provider_payment_id' => null,
            'purpose' => 'template_purchase',
            'amount' => $template->price_amount,
            'currency' => $template->price_currency,
            'status' => ((float) $template->price_amount) <= 0 ? 'paid' : 'pending',
            'payload' => [
                'cloud_template_id' => $template->id,
            ],
            'paid_at' => ((float) $template->price_amount) <= 0 ? now() : null,
        ]);

        if ($payment->status === 'paid') {
            CustomerTemplateEntitlement::query()->updateOrCreate(
                [
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'cloud_template_id' => $template->id,
                ],
                [
                    'source' => 'purchase',
                    'payment_id' => $payment->id,
                    'granted_at' => now(),
                ],
            );
        }

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'payment_url' => null,
                'status' => $payment->status,
                'manual_instruction' => $payment->status === 'pending'
                    ? 'Transfer manual/QRIS lalu kirim bukti pembayaran ke admin Dafydio Photobooth.'
                    : null,
            ],
            'meta' => [],
            'message' => 'Payment created',
        ], 201);
    }

    private function templatePayload(CloudTemplate $template, array $ownedTemplateIds, bool $hasPremium): array
    {
        $isOwned = in_array($template->id, $ownedTemplateIds, true);
        $isPremiumIncluded = $template->access_level === 'premium' && $hasPremium;

        return [
            'id' => $template->id,
            'template_code' => $template->template_code,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'paper_size' => $template->paper_size,
            'access_level' => $template->access_level,
            'price_amount' => (float) $template->price_amount,
            'price_currency' => $template->price_currency,
            'preview_path' => $template->preview_path,
            'preview_url' => $this->previewUrl($template->preview_path),
            'source_path' => $template->source_path,
            'status' => $template->status,
            'is_owned' => $isOwned,
            'is_available' => $isOwned || $isPremiumIncluded || ((float) $template->price_amount <= 0 && $template->access_level === 'marketplace'),
            'is_premium_included' => $isPremiumIncluded,
        ];
    }

    private function previewUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
