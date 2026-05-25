<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use App\Support\StationToken;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenantSlug = env('SEED_TENANT_SLUG', 'dafydio');
        $tenantName = env('SEED_TENANT_NAME', 'Dafydio');
        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@dafydio.com');
        $adminPassword = env('SEED_ADMIN_PASSWORD', 'password');
        $stationCode = env('SEED_STATION_CODE', 'STATION-001');
        $stationToken = env('SEED_STATION_TOKEN', 'station-demo-token');

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $tenantSlug],
            [
                'name' => $tenantName,
                'business_name' => env('SEED_TENANT_BUSINESS_NAME', $tenantName.' Photobooth'),
                'whatsapp_number' => env('SEED_TENANT_WHATSAPP', null),
                'email' => env('SEED_TENANT_EMAIL', $adminEmail),
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ],
        );

        $station = Station::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => $stationCode,
            ],
            [
                'name' => env('SEED_STATION_NAME', 'Main Station'),
                'api_token_hash' => Hash::make($stationToken),
                'api_token_lookup' => StationToken::lookupHash($stationToken),
                'device_identifier' => env('SEED_STATION_DEVICE_IDENTIFIER', null),
                'status' => 'active',
            ],
        );

        if (blank($station->api_token_hash) || blank($station->api_token_lookup)) {
            $station->update([
                'api_token_hash' => Hash::make($stationToken),
                'api_token_lookup' => StationToken::lookupHash($stationToken),
            ]);
        }

        User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'tenant_id' => $tenant->id,
                'name' => env('SEED_ADMIN_NAME', 'Dafydio Admin'),
                'password' => Hash::make($adminPassword),
                'role' => 'tenant_admin',
                'status' => 'active',
            ],
        );
    }
}
