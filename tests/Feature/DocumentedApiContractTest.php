<?php

namespace Tests\Feature;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\CloudTemplate;
use App\Models\Customer;
use App\Models\CustomerTemplateEntitlement;
use App\Models\Station;
use App\Models\Tenant;
use App\Models\User;
use App\Support\StationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentedApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_can_upsert_customer_with_station_created_password(): void
    {
        [$station, $token] = $this->createStation();

        $customerId = $this->withToken($token)
            ->postJson('/api/station/customers', [
                'name' => 'Customer Sync',
                'whatsapp_number' => '+628122222222',
                'password' => 'secret123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Customer synced')
            ->json('data.customer_id');

        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'tenant_id' => $station->tenant_id,
            'whatsapp_number' => '+628122222222',
        ]);

        $this->assertTrue(Hash::check('secret123', Customer::query()->findOrFail($customerId)->password));
    }

    public function test_station_can_sync_customer_cloud_account_password(): void
    {
        [$station, $token] = $this->createStation();

        $customerId = $this->withToken($token)
            ->postJson('/api/station/customers/cloud-account', [
                'customer_whatsapp' => '6281234567890',
                'username' => '6281234567890',
                'password' => 'new-secret-password',
                'tier' => 'regular',
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Customer cloud account synced')
            ->assertJsonPath('data.customer_whatsapp', '6281234567890')
            ->json('data.customer_id');

        $customer = Customer::query()->findOrFail($customerId);

        $this->assertSame($station->tenant_id, $customer->tenant_id);
        $this->assertTrue(Hash::check('new-secret-password', $customer->password));
        $this->assertDatabaseHas('customer_subscriptions', [
            'tenant_id' => $station->tenant_id,
            'customer_id' => $customer->id,
            'plan' => 'regular',
            'status' => 'active',
        ]);
    }

    public function test_customer_can_purchase_free_template_and_create_edit_job(): void
    {
        [$customer, $session, $asset] = $this->createCustomerSession();
        Storage::disk('public')->put($asset->path, $this->jpegBytes(640, 960, [0, 74, 198]));
        Storage::disk('public')->put('templates/free-frame.jpg', $this->jpegBytes(800, 1200, [254, 166, 25]));

        $template = CloudTemplate::query()->create([
            'tenant_id' => $customer->tenant_id,
            'name' => 'Free Frame',
            'access_level' => 'marketplace',
            'price_amount' => 0,
            'price_currency' => 'IDR',
            'preview_path' => 'templates/free-frame.jpg',
            'source_path' => 'templates/free-frame.jpg',
            'slots' => [
                [
                    'slot_index' => 1,
                    'x' => 100,
                    'y' => 120,
                    'width' => 600,
                    'height' => 900,
                ],
            ],
            'status' => 'active',
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/customer/templates')
            ->assertOk()
            ->assertJsonPath('data.0.id', $template->id)
            ->assertJsonPath('data.0.preview_url', url('/storage/templates/free-frame.jpg'))
            ->assertJsonPath('data.0.is_owned', false);

        $paymentId = $this->withToken($token)
            ->postJson("/api/customer/templates/{$template->id}/purchase")
            ->assertCreated()
            ->assertJsonPath('data.status', 'paid')
            ->json('data.payment_id');

        $this->assertDatabaseHas('customer_template_entitlements', [
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'cloud_template_id' => $template->id,
            'payment_id' => $paymentId,
        ]);

        $this->withToken($token)
            ->getJson('/api/customer/templates')
            ->assertOk()
            ->assertJsonPath('data.0.is_owned', true);

        $this->withToken($token)
            ->postJson('/api/customer/edit-jobs', [
                'cloud_session_id' => $session->id,
                'source_asset_id' => $asset->id,
                'cloud_template_id' => $template->id,
                'editor_payload' => ['crop' => 'square'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('cloud_session_assets', [
            'tenant_id' => $customer->tenant_id,
            'cloud_session_id' => $session->id,
            'type' => 'edited',
            'status' => 'uploaded',
        ]);

        $this->withToken($token)
            ->getJson('/api/customer/edit-jobs')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.template_name', 'Free Frame')
            ->assertJsonPath('data.0.session_title', 'Event Test')
            ->assertJsonPath('data.0.result_asset.type', 'edited');
    }

    public function test_customer_cannot_edit_with_unowned_marketplace_template(): void
    {
        [$customer, $session, $asset] = $this->createCustomerSession();

        $template = CloudTemplate::query()->create([
            'tenant_id' => $customer->tenant_id,
            'name' => 'Paid Frame',
            'access_level' => 'marketplace',
            'price_amount' => 50000,
            'price_currency' => 'IDR',
            'status' => 'active',
        ]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson('/api/customer/edit-jobs', [
                'cloud_session_id' => $session->id,
                'source_asset_id' => $asset->id,
                'cloud_template_id' => $template->id,
            ])
            ->assertForbidden();
    }

    public function test_manual_template_payment_requires_admin_approval_before_entitlement(): void
    {
        [$customer] = $this->createCustomerSession();
        [$user] = $this->createTenantAdmin('billing@example.com', 'billing-tenant');
        $user->tenant_id = $customer->tenant_id;
        $user->save();

        $template = CloudTemplate::query()->create([
            'tenant_id' => $customer->tenant_id,
            'name' => 'Paid Elegant Frame',
            'access_level' => 'marketplace',
            'price_amount' => 75000,
            'price_currency' => 'IDR',
            'status' => 'active',
        ]);

        $token = $customer->createToken('test')->plainTextToken;

        $paymentId = $this->withToken($token)
            ->postJson("/api/customer/templates/{$template->id}/purchase")
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.manual_instruction', 'Transfer manual/QRIS lalu kirim bukti pembayaran ke admin Dafydio Photobooth.')
            ->json('data.payment_id');

        $this->assertDatabaseMissing('customer_template_entitlements', [
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'cloud_template_id' => $template->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/customer/payments')
            ->assertOk()
            ->assertJsonPath('data.0.id', $paymentId)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.template_name', 'Paid Elegant Frame');

        $this->actingAs($user)
            ->post("/admin/payments/{$paymentId}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('customer_template_entitlements', [
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'cloud_template_id' => $template->id,
            'payment_id' => $paymentId,
        ]);

        $this->app['auth']->guard('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $templates = $this->withToken($token)
            ->getJson('/api/customer/templates')
            ->assertOk()
            ->json('data');

        $ownedTemplate = collect($templates)->firstWhere('id', $template->id);

        $this->assertNotNull($ownedTemplate);
        $this->assertTrue($ownedTemplate['is_owned']);
    }

    public function test_admin_api_is_tenant_scoped_and_manages_templates(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [, $otherTenant] = $this->createTenantAdmin('other@example.com', 'other-tenant');

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Owned Station',
            'code' => 'OWN-001',
            'status' => 'active',
        ]);

        Station::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Station',
            'code' => 'OTH-001',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson('/api/admin/stations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $station->id);

        $templateId = $this->actingAs($user)
            ->postJson('/api/admin/templates', [
                'name' => 'Admin Template',
                'access_level' => 'marketplace',
                'price_amount' => 25000,
                'price_currency' => 'IDR',
                'status' => 'active',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($user)
            ->patchJson("/api/admin/templates/{$templateId}", [
                'name' => 'Updated Template',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Template');
    }

    public function test_customer_token_cannot_access_admin_api(): void
    {
        [$customer] = $this->createCustomerSession();

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson('/api/admin/stations')
            ->assertForbidden();
    }

    private function createStation(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $token = 'station-token';

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Station Test',
            'code' => 'ST-001',
            'api_token_hash' => Hash::make($token),
            'api_token_lookup' => StationToken::lookupHash($token),
            'status' => 'active',
        ]);

        return [$station, $token];
    }

    private function createCustomerSession(): array
    {
        [$station] = $this->createStation();

        $customer = Customer::query()->create([
            'tenant_id' => $station->tenant_id,
            'name' => 'Customer Test',
            'whatsapp_number' => '+628111111111',
            'password' => 'password',
            'status' => 'active',
        ]);

        $session = CloudSession::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'local-session-1',
            'title' => 'Event Test',
            'sync_status' => 'complete',
        ]);

        $asset = CloudSessionAsset::query()->create([
            'tenant_id' => $station->tenant_id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'asset-1',
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/test/session/photo.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        return [$customer, $session, $asset];
    }

    private function createTenantAdmin(string $email = 'admin@example.com', string $slug = 'test-tenant'): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => $slug,
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => $email,
            'password' => 'password',
            'role' => 'tenant_admin',
            'status' => 'active',
        ]);

        return [$user, $tenant];
    }

    private function jpegBytes(int $width, int $height, array $rgb): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]));

        ob_start();
        imagejpeg($image, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
