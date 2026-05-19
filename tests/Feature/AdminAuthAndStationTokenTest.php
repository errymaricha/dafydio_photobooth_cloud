<?php

namespace Tests\Feature;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\CloudTemplate;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use App\Models\Station;
use App\Models\StationSyncLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthAndStationTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_legacy_admin_login_redirects_to_unified_login(): void
    {
        $this->get('/admin/login')
            ->assertRedirect('/login?mode=admin');
    }

    public function test_legacy_customer_login_redirects_to_unified_login(): void
    {
        $this->get('/customer/login')
            ->assertRedirect('/login?mode=customer');
    }

    public function test_tenant_admin_can_login_and_view_stations(): void
    {
        [$user] = $this->createTenantAdmin();

        $this->post('/login/admin', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);

        $this->get('/admin/stations')
            ->assertOk();
    }

    public function test_tenant_admin_dashboard_has_operational_props(): void
    {
        [$user] = $this->createTenantAdmin();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('metrics')
                ->has('recentStations')
                ->has('recentSessions')
                ->has('printRequests')
                ->has('syncLogs')
                ->has('storage')
                ->has('deployment')
            );
    }

    public function test_tenant_admin_can_regenerate_station_token(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Station',
            'code' => 'TEST-001',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post("/admin/stations/{$station->id}/token")
            ->assertRedirect()
            ->assertSessionHas('station_token.token');

        $this->assertNotNull($station->refresh()->api_token_hash);
    }

    public function test_tenant_admin_can_view_session_detail_with_customer_and_assets(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Station',
            'code' => 'TEST-001',
            'status' => 'active',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Customer Test',
            'whatsapp_number' => '628122222222',
            'password' => 'password',
            'status' => 'active',
        ]);

        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'session-1',
            'title' => 'Session Test',
            'sync_status' => 'complete',
        ]);

        CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'asset-1',
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/test/photo.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        $this->actingAs($user)
            ->get("/admin/sessions/{$session->id}?from_customer=1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sessions/Show')
                ->where('session.id', $session->id)
                ->where('customer.whatsapp_number', '628122222222')
                ->where('backToCustomerUrl', url("/admin/customers/{$customer->id}"))
                ->has('assets', 1)
                ->where('assets.0.file_url', url('/storage/tenants/test/photo.jpg'))
            );
    }

    public function test_tenant_admin_can_view_sessions_menu(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [$session] = $this->createSessionArchive($tenant);

        $this->actingAs($user)
            ->get('/admin/sessions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sessions/Index')
                ->where('sessions.data.0.id', $session->id)
            );
    }

    public function test_tenant_admin_can_filter_guest_sessions(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        $this->createSessionArchive($tenant);

        $station = Station::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $guestSession = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => null,
            'station_session_id' => 'guest-session-1',
            'title' => 'Guest Session Test',
            'sync_status' => 'complete',
            'metadata' => [
                'is_guest' => true,
                'station_session' => [
                    'session_code' => 'SES-GUEST-001',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/sessions?identity=guests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sessions/Index')
                ->where('filters.identity', 'guests')
                ->where('sessions.data.0.id', $guestSession->id)
                ->where('sessions.data.0.customer_name', 'Guest - SES-GUEST-001')
                ->where('sessions.data.0.is_guest', true)
                ->has('sessions.data', 1)
            );
    }

    public function test_tenant_admin_can_search_sessions_archive(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [$session] = $this->createSessionArchive($tenant);

        $this->actingAs($user)
            ->get('/admin/sessions?q=628122222222')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sessions/Index')
                ->where('filters.q', '628122222222')
                ->where('sessions.data.0.id', $session->id)
                ->has('sessions.data', 1)
            );

        $this->actingAs($user)
            ->get('/admin/sessions?q=TEST-001&status=complete')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sessions/Index')
                ->where('filters.q', 'TEST-001')
                ->where('filters.status', 'complete')
                ->where('sessions.data.0.id', $session->id)
                ->has('sessions.data', 1)
            );
    }

    public function test_tenant_admin_can_view_and_filter_sync_logs(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Sync Station',
            'code' => 'SYNC-001',
            'status' => 'active',
        ]);

        $log = StationSyncLog::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'direction' => 'station_to_cloud',
            'topic' => 'asset-upload',
            'idempotency_key' => 'station:SYNC-001:asset:123',
            'status' => 'ok',
            'payload' => ['session_code' => 'SES-SYNC-001'],
            'response' => ['message' => 'Asset file received'],
        ]);

        $this->actingAs($user)
            ->get('/admin/sync-logs?q=SES-SYNC-001&topic=asset-upload&status=ok')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/SyncLogs/Index')
                ->where('filters.q', 'SES-SYNC-001')
                ->where('filters.topic', 'asset-upload')
                ->where('filters.status', 'ok')
                ->where('logs.data.0.id', $log->id)
                ->where('logs.data.0.station_code', 'SYNC-001')
                ->where('logs.data.0.payload.session_code', 'SES-SYNC-001')
                ->has('logs.data', 1)
            );
    }

    public function test_tenant_admin_can_link_guest_session_to_customer(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Station',
            'code' => 'TEST-001',
            'status' => 'active',
        ]);
        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => null,
            'station_session_id' => 'guest-session-link-admin',
            'title' => 'Guest Admin Link',
            'sync_status' => 'complete',
            'metadata' => [
                'is_guest' => true,
                'station_session' => [
                    'session_code' => 'SES-ADMIN-LINK',
                    'customer_whatsapp' => null,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post("/admin/sessions/{$session->id}/link-customer", [
                'customer_whatsapp' => '6282118401998',
                'customer_name' => 'Admin Linked',
                'customer_tier' => 'regular',
            ])
            ->assertRedirect("/admin/sessions/{$session->id}");

        $session->refresh();
        $customer = Customer::query()->where('whatsapp_number', '6282118401998')->firstOrFail();

        $this->assertSame($customer->id, $session->customer_id);
        $this->assertFalse($session->metadata['is_guest']);
        $this->assertSame('6282118401998', $session->metadata['station_session']['customer_whatsapp']);
        $this->assertNotSame('unknown', Hash::info($customer->password)['algoName']);
        $this->assertDatabaseHas('customer_subscriptions', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'plan' => 'regular',
            'status' => 'active',
        ]);
    }

    public function test_tenant_admin_can_view_customers_menu(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [, $customer] = $this->createSessionArchive($tenant);

        $this->actingAs($user)
            ->get('/admin/customers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Index')
                ->where('customers.data.0.id', $customer->id)
            );
    }

    public function test_tenant_admin_can_search_and_filter_customers(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [, $regularCustomer] = $this->createSessionArchive($tenant);

        $premiumCustomer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Guest',
            'whatsapp_number' => '628133333333',
            'password' => 'password',
            'status' => 'active',
        ]);

        CustomerSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $regularCustomer->id,
            'plan' => 'regular',
            'status' => 'active',
        ]);

        CustomerSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $premiumCustomer->id,
            'plan' => 'premium',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/admin/customers?q=628122222222')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Index')
                ->where('filters.q', '628122222222')
                ->where('filters.plan', 'all')
                ->where('customers.data.0.id', $regularCustomer->id)
                ->has('customers.data', 1)
            );

        $this->actingAs($user)
            ->get('/admin/customers?q=premium&plan=premium')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Index')
                ->where('filters.q', 'premium')
                ->where('filters.plan', 'premium')
                ->where('customers.data.0.id', $premiumCustomer->id)
                ->where('customers.data.0.subscription_plan', 'premium')
                ->has('customers.data', 1)
            );
    }

    public function test_tenant_admin_can_update_customer_name(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [, $customer] = $this->createSessionArchive($tenant);

        $this->actingAs($user)
            ->patch("/admin/customers/{$customer->id}", [
                'name' => 'Nama Baru Customer',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Nama customer berhasil diperbarui.');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'tenant_id' => $tenant->id,
            'name' => 'Nama Baru Customer',
        ]);
    }

    public function test_tenant_admin_cannot_update_customer_from_other_tenant(): void
    {
        [$user] = $this->createTenantAdmin();
        $otherTenant = Tenant::query()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
        ]);

        $otherCustomer = Customer::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Customer',
            'whatsapp_number' => '628144444444',
            'password' => 'password',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->patch("/admin/customers/{$otherCustomer->id}", [
                'name' => 'Nama Tidak Boleh',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('customers', [
            'id' => $otherCustomer->id,
            'name' => 'Other Customer',
        ]);
    }

    public function test_tenant_admin_can_view_customer_detail_with_sessions(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [$session, $customer] = $this->createSessionArchive($tenant);

        $this->actingAs($user)
            ->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Customers/Show')
                ->where('customer.id', $customer->id)
                ->where('sessions.data.0.id', $session->id)
                ->where('sessions.data.0.public_url', route('public.sessions.show', ['sessionCode' => 'session-1']))
            );
    }

    public function test_tenant_admin_can_manage_templates_menu(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();

        $template = CloudTemplate::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Wedding Classic',
            'access_level' => 'marketplace',
            'price_amount' => 0,
            'price_currency' => 'IDR',
            'preview_path' => 'templates/wedding.jpg',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/admin/templates')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Templates/Index')
                ->where('templates.data.0.id', $template->id)
                ->where('templates.data.0.preview_url', url('/storage/templates/wedding.jpg'))
                ->where('metrics.total', 1)
            );

        $this->actingAs($user)
            ->post('/admin/templates', [
                'name' => 'Birthday Pop',
                'access_level' => 'premium',
                'price_amount' => 0,
                'price_currency' => 'IDR',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cloud_templates', [
            'tenant_id' => $tenant->id,
            'name' => 'Birthday Pop',
            'access_level' => 'premium',
        ]);
    }

    public function test_tenant_admin_can_view_and_reject_manual_payments(): void
    {
        [$user, $tenant] = $this->createTenantAdmin();
        [, $customer] = $this->createSessionArchive($tenant);

        $template = CloudTemplate::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Manual Pay Frame',
            'access_level' => 'marketplace',
            'price_amount' => 55000,
            'price_currency' => 'IDR',
            'status' => 'active',
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'provider' => 'manual',
            'purpose' => 'template_purchase',
            'amount' => 55000,
            'currency' => 'IDR',
            'status' => 'pending',
            'payload' => [
                'cloud_template_id' => $template->id,
            ],
        ]);

        $this->actingAs($user)
            ->get('/admin/payments?q=628122222222&status=pending')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Payments/Index')
                ->where('filters.q', '628122222222')
                ->where('filters.status', 'pending')
                ->where('payments.data.0.id', $payment->id)
                ->where('payments.data.0.template_name', 'Manual Pay Frame')
                ->where('payments.data.0.customer_whatsapp', '628122222222')
                ->has('payments.data', 1)
            );

        $this->actingAs($user)
            ->post("/admin/payments/{$payment->id}/reject")
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
    }

    private function createTenantAdmin(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'tenant_admin',
            'status' => 'active',
        ]);

        return [$user, $tenant];
    }

    private function createSessionArchive(Tenant $tenant): array
    {
        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Station',
            'code' => 'TEST-001',
            'status' => 'active',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Customer Test',
            'whatsapp_number' => '628122222222',
            'password' => 'password',
            'status' => 'active',
        ]);

        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'session-1',
            'title' => 'Session Test',
            'sync_status' => 'complete',
        ]);

        CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'asset-1',
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/test/photo.jpg',
            'status' => 'uploaded',
        ]);

        return [$session, $customer, $station];
    }
}
