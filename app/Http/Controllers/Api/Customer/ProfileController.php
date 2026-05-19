<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $customer = $request->user();
        $customer->update([
            'name' => trim($data['name']),
        ]);

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'subscription_plan' => 'regular',
                ],
            ],
            'meta' => [],
            'message' => 'Nama berhasil disimpan.',
        ]);
    }
}
