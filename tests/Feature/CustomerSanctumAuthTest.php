<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_with_whatsapp_password_and_receive_sanctum_token(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $response = $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => $customer->whatsapp_number,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'customer' => [
                        'id',
                        'name',
                        'subscription_plan',
                    ],
                ],
                'meta',
                'message',
            ]);
    }

    public function test_customer_can_login_with_local_zero_whatsapp_format(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => '08111111111',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id);
    }

    public function test_customer_can_login_with_country_code_without_plus(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => '628111111111',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id);
    }

    public function test_customer_can_use_sanctum_token_and_logout(): void
    {
        [$tenant, $customer] = $this->createCustomer();
        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Station Test',
            'code' => 'ST-001',
            'status' => 'active',
        ]);
        CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'older-local-session-id',
            'title' => 'Older Session',
            'started_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
            'sync_status' => 'complete',
            'metadata' => [
                'station_session' => [
                    'session_code' => 'SES-OLDER-001',
                ],
            ],
        ]);
        $session = CloudSession::query()->create([
            'tenant_id' => $tenant->id,
            'station_id' => $station->id,
            'customer_id' => $customer->id,
            'station_session_id' => 'local-session-id',
            'title' => 'Wedding Session',
            'sync_status' => 'complete',
            'metadata' => [
                'station_session' => [
                    'session_code' => 'SES-CUSTOMER-001',
                ],
            ],
        ]);
        CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'framed-1',
            'type' => 'framed',
            'disk' => 'public',
            'path' => 'tenants/test/customer/framed.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        $token = $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => $customer->whatsapp_number,
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)
            ->getJson('/api/customer/sessions')
            ->assertOk()
            ->assertJsonPath('data.0.session_code', 'SES-CUSTOMER-001')
            ->assertJsonPath('data.0.public_url', route('public.sessions.show', ['sessionCode' => 'SES-CUSTOMER-001']))
            ->assertJsonPath('data.0.download_all_url', route('public.sessions.download', ['sessionCode' => 'SES-CUSTOMER-001']))
            ->assertJsonPath('data.0.assets.0.file_url', url('/storage/tenants/test/customer/framed.jpg'));

        $this->withToken($token)
            ->postJson('/api/customer/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_customer_can_update_own_name(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $token = $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => $customer->whatsapp_number,
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)
            ->patchJson('/api/customer/profile', [
                'name' => 'Nama Customer Baru',
            ])
            ->assertOk()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Nama Customer Baru')
            ->assertJsonPath('message', 'Nama berhasil disimpan.');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Nama Customer Baru',
        ]);
    }

    public function test_customer_name_is_required_when_updating_profile(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $token = $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => $customer->whatsapp_number,
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)
            ->patchJson('/api/customer/profile', [
                'name' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_customer_login_rejects_wrong_password(): void
    {
        [$tenant, $customer] = $this->createCustomer();

        $this->postJson('/api/customer/auth/login', [
            'tenant_slug' => $tenant->slug,
            'whatsapp_number' => $customer->whatsapp_number,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    private function createCustomer(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Customer',
            'whatsapp_number' => '+628111111111',
            'password' => 'password',
            'status' => 'active',
        ]);

        return [$tenant, $customer];
    }
}
