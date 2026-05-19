<?php

namespace Tests\Feature;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAssetDownloadUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_request_download_url_for_uploaded_asset(): void
    {
        [$customer, $asset] = $this->createUploadedAsset();

        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/assets/{$asset->id}/download-url")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'download_url',
                    'expires_at',
                ],
                'meta',
                'message',
            ])
            ->assertJsonPath('data.download_url', url('/storage/tenants/test/session/photo.jpg'));
    }

    public function test_customer_cannot_request_download_url_for_another_customer_asset(): void
    {
        [$customer] = $this->createUploadedAsset();
        [, $otherAsset] = $this->createUploadedAsset('other-tenant', '+628222222222');

        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/assets/{$otherAsset->id}/download-url")
            ->assertNotFound();
    }

    public function test_customer_cannot_download_pending_asset(): void
    {
        [$customer, $asset] = $this->createUploadedAsset();

        $asset->update(['status' => 'pending_upload']);

        Sanctum::actingAs($customer);

        $this->postJson("/api/customer/assets/{$asset->id}/download-url")
            ->assertUnprocessable();
    }

    private function createUploadedAsset(string $tenantSlug = 'test-tenant', string $whatsapp = '+628111111111'): array
    {
        $tenant = Tenant::query()->create([
            'name' => str($tenantSlug)->headline()->toString(),
            'slug' => $tenantSlug,
            'status' => 'active',
        ]);

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Station',
            'code' => strtoupper($tenantSlug),
            'status' => 'active',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Customer',
            'whatsapp_number' => $whatsapp,
            'password' => 'password',
            'status' => 'active',
        ]);

        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'station-session-'.$tenantSlug,
            'title' => 'Test Session',
            'sync_status' => 'complete',
        ]);

        $asset = CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'photo-1',
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/test/session/photo.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        return [$customer, $asset];
    }
}
