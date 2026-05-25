<?php

namespace Tests\Feature;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudRetentionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloud_retention_archives_customer_sessions_after_three_inactive_months(): void
    {
        [$session] = $this->createCustomerSession(now()->subMonths(4));

        $this->artisan('cloud:enforce-retention')
            ->assertSuccessful();

        $session->refresh();

        $this->assertSame('archived', $session->sync_status);
        $this->assertNotNull($session->archived_at);
        $this->assertNull($session->assets_deleted_at);
    }

    public function test_cloud_retention_deletes_asset_files_after_five_inactive_months(): void
    {
        Storage::fake('public');

        [$session, $asset] = $this->createCustomerSession(now()->subMonths(6), 'old-photo.jpg');

        Storage::disk('public')->put($asset->path, 'photo-bytes');
        Storage::disk('public')->assertExists($asset->path);

        $this->artisan('cloud:enforce-retention')
            ->assertSuccessful();

        $session->refresh();
        $asset->refresh();

        $this->assertSame('deleted', $session->sync_status);
        $this->assertNotNull($session->archived_at);
        $this->assertNotNull($session->assets_deleted_at);
        $this->assertSame('deleted', $asset->status);
        $this->assertNotNull($asset->deleted_at);
        Storage::disk('public')->assertMissing($asset->path);
    }

    public function test_cloud_retention_keeps_customer_with_recent_session_active(): void
    {
        [$oldSession] = $this->createCustomerSession(now()->subMonths(6));
        $this->createCustomerSession(now()->subWeeks(2), 'recent-photo.jpg', $oldSession->customer);

        $this->artisan('cloud:enforce-retention')
            ->assertSuccessful();

        $oldSession->refresh();

        $this->assertSame('complete', $oldSession->sync_status);
        $this->assertNull($oldSession->archived_at);
    }

    public function test_cloud_retention_dry_run_does_not_change_database_or_files(): void
    {
        Storage::fake('public');

        [$session, $asset] = $this->createCustomerSession(now()->subMonths(6), 'dry-run-photo.jpg');

        Storage::disk('public')->put($asset->path, 'photo-bytes');

        $this->artisan('cloud:enforce-retention --dry-run')
            ->assertSuccessful();

        $session->refresh();
        $asset->refresh();

        $this->assertSame('complete', $session->sync_status);
        $this->assertSame('uploaded', $asset->status);
        Storage::disk('public')->assertExists($asset->path);
    }

    private function createCustomerSession($date, string $assetName = 'photo.jpg', ?Customer $customer = null): array
    {
        $tenant = $customer?->tenant ?? Tenant::query()->create([
            'name' => 'Retention Tenant',
            'slug' => 'retention-'.str()->ulid(),
            'status' => 'active',
        ]);

        $station = Station::query()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'code' => 'RETENTION-001',
        ], [
            'name' => 'Retention Station',
            'status' => 'active',
        ]);

        $customer ??= Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Retention Customer',
            'whatsapp_number' => '628'.random_int(100000000, 999999999),
            'password' => 'password',
            'status' => 'active',
        ]);

        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'station-session-'.str()->ulid(),
            'title' => 'Retention Session',
            'started_at' => $date,
            'ended_at' => $date,
            'sync_status' => 'complete',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        $asset = CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'asset-'.str()->ulid(),
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/retention/'.$assetName,
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        return [$session, $asset, $customer];
    }
}
