<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Support\StationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Station::query()
                ->where('tenant_id', $request->user()->tenant_id)
                ->latest()
                ->paginate(20)
                ->through(fn (Station $station): array => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'code' => $station->code,
                    'device_identifier' => $station->device_identifier,
                    'app_version' => $station->app_version,
                    'last_seen_at' => $station->last_seen_at,
                    'status' => $station->status,
                    'has_token' => filled($station->api_token_hash),
                ])
                ->items(),
            'meta' => [],
            'message' => null,
        ]);
    }

    public function token(Request $request, Station $station): JsonResponse
    {
        abort_unless($station->tenant_id === $request->user()->tenant_id, 404);

        $token = 'st_'.$station->code.'_'.Str::random(40);
        $station->update([
            'api_token_hash' => Hash::make($token),
            'api_token_lookup' => StationToken::lookupHash($token),
        ]);

        return response()->json([
            'data' => [
                'station_id' => $station->id,
                'token' => $token,
            ],
            'meta' => [],
            'message' => 'Station token created',
        ]);
    }
}
