<?php

namespace Database\Seeders;

use App\Models\CloudPrintRequest;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Station;
use App\Models\StationSyncLog;
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
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'dafydio-demo'],
            [
                'name' => 'Dafydio Demo',
                'business_name' => 'Dafydio Photobooth',
                'whatsapp_number' => '+6280000000000',
                'email' => 'demo@dafydio.local',
                'timezone' => 'Asia/Jakarta',
                'status' => 'active',
            ],
        );

        $station = Station::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'code' => 'STATION-001',
            ],
            [
                'name' => 'Demo Station',
                'api_token_hash' => Hash::make('station-demo-token'),
                'api_token_lookup' => StationToken::lookupHash('station-demo-token'),
                'device_identifier' => 'demo-android-station',
                'app_version' => '1.0.0',
                'last_seen_at' => now()->subMinutes(2),
                'status' => 'active',
            ],
        );

        if (blank($station->api_token_lookup)) {
            $station->update([
                'api_token_lookup' => StationToken::lookupHash('station-demo-token'),
            ]);
        }

        User::query()->firstOrCreate(
            ['email' => 'admin@dafydio.local'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Dafydio Admin',
                'password' => Hash::make('password'),
                'role' => 'tenant_admin',
                'status' => 'active',
            ],
        );

        $customer = Customer::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'whatsapp_number' => '+628111111111',
            ],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('password'),
                'status' => 'active',
            ],
        );

        CustomerSubscription::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
            ],
            [
                'plan' => 'regular',
                'status' => 'active',
                'starts_at' => now()->subDays(7),
                'print_quota' => 0,
                'storage_retention_days' => 30,
            ],
        );

        $session = CloudSession::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'station_id' => $station->id,
                'station_session_id' => 'demo-session-001',
            ],
            [
                'customer_id' => $customer->id,
                'title' => 'Demo Wedding Session',
                'started_at' => now()->subHours(2),
                'ended_at' => now()->subHour(),
                'sync_status' => 'complete',
                'metadata' => [
                    'event_type' => 'wedding',
                    'source' => 'database_seeder',
                ],
            ],
        );

        $asset = CloudSessionAsset::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cloud_session_id' => $session->id,
                'station_asset_id' => 'demo-photo-001',
            ],
            [
                'type' => 'original',
                'disk' => 'public',
                'path' => 'tenants/demo/sessions/demo-session-001/original/demo-photo-001.jpg',
                'mime_type' => 'image/jpeg',
                'size_bytes' => 1200000,
                'width' => 1800,
                'height' => 1200,
                'status' => 'uploaded',
            ],
        );

        CloudSessionAsset::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cloud_session_id' => $session->id,
                'station_asset_id' => 'demo-photo-001-framed',
            ],
            [
                'type' => 'framed',
                'disk' => 'public',
                'path' => 'tenants/demo/sessions/demo-session-001/framed/demo-photo-001-framed.jpg',
                'mime_type' => 'image/jpeg',
                'size_bytes' => 1400000,
                'width' => 1800,
                'height' => 1200,
                'status' => 'uploaded',
            ],
        );

        CloudPrintRequest::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'station_id' => $station->id,
                'customer_id' => $customer->id,
                'cloud_session_id' => $session->id,
                'cloud_session_asset_id' => $asset->id,
            ],
            [
                'quantity' => 1,
                'status' => 'pending',
                'priority' => 'normal',
                'payment_status' => 'not_required',
            ],
        );

        StationSyncLog::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'station_id' => $station->id,
                'topic' => 'demo-session-sync',
            ],
            [
                'direction' => 'station_to_cloud',
                'status' => 'ok',
                'payload' => ['station_session_id' => 'demo-session-001'],
                'response' => ['sync_status' => 'complete'],
            ],
        );
    }
}
