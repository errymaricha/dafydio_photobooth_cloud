<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_slug' => ['required', 'string', 'exists:tenants,slug'],
            'whatsapp_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $tenant = Tenant::query()->where('slug', $data['tenant_slug'])->firstOrFail();

        $customer = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('whatsapp_number', WhatsAppNumber::lookupVariants($data['whatsapp_number']))
            ->where('status', 'active')
            ->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            abort(422, 'Invalid WhatsApp number or password.');
        }

        $customer->update(['last_login_at' => now()]);

        return response()->json([
            'data' => [
                'token' => $customer->createToken('customer-portal')->plainTextToken,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'subscription_plan' => 'regular',
                ],
            ],
            'meta' => [],
            'message' => 'Logged in',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'data' => null,
            'meta' => [],
            'message' => 'Logged out',
        ]);
    }
}
