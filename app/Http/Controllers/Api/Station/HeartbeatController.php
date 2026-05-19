<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'local_time' => ['nullable', 'date'],
        ]);

        $station = $request->attributes->get('station');
        $station->update([
            'device_identifier' => $data['device_identifier'] ?? $station->device_identifier,
            'app_version' => $data['app_version'] ?? $station->app_version,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'station_id' => $station->id,
                'server_time' => now()->toISOString(),
            ],
            'meta' => [],
            'message' => 'OK',
        ]);
    }
}
