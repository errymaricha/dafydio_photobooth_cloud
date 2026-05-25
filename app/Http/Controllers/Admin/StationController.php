<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Support\StationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    public function index(Request $request): Response
    {
        $stations = Station::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Stations/Index', [
            'stations' => $stations->through(fn (Station $station): array => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'device_identifier' => $station->device_identifier,
                'app_version' => $station->app_version,
                'last_seen_at' => $station->last_seen_at?->diffForHumans(),
                'status' => $station->status,
                'has_token' => filled($station->api_token_hash),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $request->user()->tenant_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('stations', 'code')->where('tenant_id', $tenantId),
            ],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'maintenance'])],
            'generate_token' => ['sometimes', 'boolean'],
        ]);

        $token = null;
        $stationData = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'code' => Str::upper($data['code']),
            'device_identifier' => $data['device_identifier'] ?? null,
            'status' => $data['status'],
        ];

        if ($request->boolean('generate_token', true)) {
            $token = 'st_'.$stationData['code'].'_'.Str::random(40);
            $stationData['api_token_hash'] = Hash::make($token);
            $stationData['api_token_lookup'] = StationToken::lookupHash($token);
        }

        $station = Station::query()->create($stationData);

        $response = back()->with('message', 'Station berhasil ditambahkan.');

        if ($token) {
            $response->with('station_token', [
                'station_id' => $station->id,
                'station_code' => $station->code,
                'token' => $token,
            ]);
        }

        return $response;
    }

    public function regenerateToken(Request $request, Station $station): RedirectResponse
    {
        abort_unless($station->tenant_id === $request->user()->tenant_id, 404);

        $token = 'st_'.$station->code.'_'.Str::random(40);

        $station->update([
            'api_token_hash' => Hash::make($token),
            'api_token_lookup' => StationToken::lookupHash($token),
        ]);

        return back()
            ->with('message', 'Token station berhasil dibuat ulang. Simpan token ini sekarang karena tidak akan ditampilkan lagi.')
            ->with('station_token', [
                'station_id' => $station->id,
                'station_code' => $station->code,
                'token' => $token,
            ]);
    }
}
