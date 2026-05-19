<?php

namespace App\Http\Middleware;

use App\Models\Station;
use App\Support\StationToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStation
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            abort(401, 'Station token is required.');
        }

        $station = Station::query()
            ->where('status', 'active')
            ->where('api_token_lookup', StationToken::lookupHash($token))
            ->first();

        if (! $station) {
            $station = Station::query()
                ->where('status', 'active')
                ->whereNull('api_token_lookup')
                ->whereNotNull('api_token_hash')
                ->get()
                ->first(fn (Station $station): bool => Hash::check($token, $station->api_token_hash));

            if ($station) {
                $station->forceFill([
                    'api_token_lookup' => StationToken::lookupHash($token),
                ])->save();
            }
        }

        if (! $station) {
            abort(401, 'Invalid station token.');
        }

        $request->attributes->set('station', $station);
        $request->attributes->set('tenant_id', $station->tenant_id);

        return $next($request);
    }
}
