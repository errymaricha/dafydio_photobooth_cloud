<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $customer = Customer::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'whatsapp_number' => $data['whatsapp_number'],
            ],
            [
                'name' => $data['name'] ?? null,
                'password' => $data['password'],
                'status' => 'active',
            ],
        );

        return response()->json([
            'data' => [
                'customer_id' => $customer->id,
                'whatsapp_number' => $customer->whatsapp_number,
            ],
            'meta' => [],
            'message' => 'Customer synced',
        ]);
    }

    public function cloudAccount(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');

        $data = $request->validate([
            'customer_whatsapp' => ['required', 'string', 'max:32'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'tier' => ['nullable', 'in:regular,premium'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $customer = Customer::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'whatsapp_number' => $data['customer_whatsapp'],
            ],
            [
                'name' => $data['username'] ?? $data['customer_whatsapp'],
                'password' => $data['password'],
                'status' => $data['status'] ?? 'active',
            ],
        );

        CustomerSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'customer_id' => $customer->id,
            ],
            [
                'plan' => $data['tier'] ?? 'regular',
                'status' => 'active',
                'starts_at' => now(),
                'print_quota' => ($data['tier'] ?? 'regular') === 'premium' ? 10 : 0,
                'storage_retention_days' => ($data['tier'] ?? 'regular') === 'premium' ? 365 : 30,
            ],
        );

        return response()->json([
            'data' => [
                'customer_id' => $customer->id,
                'customer_whatsapp' => $customer->whatsapp_number,
                'username' => $data['username'] ?? $customer->whatsapp_number,
                'tier' => $data['tier'] ?? 'regular',
                'status' => $customer->status,
            ],
            'meta' => [],
            'message' => 'Customer cloud account synced',
        ]);
    }
}
