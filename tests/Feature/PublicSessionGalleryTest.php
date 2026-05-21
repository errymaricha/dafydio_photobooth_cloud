<?php

namespace Tests\Feature;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use App\Models\Station;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSessionGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_session_code_shows_uploaded_original_and_framed_assets(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Station Test',
            'code' => 'ST-001',
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
            'station_session_id' => 'local-session-id',
            'title' => 'Session Test',
            'sync_status' => 'complete',
            'metadata' => [
                'station_session' => [
                    'session_code' => 'SES-NTRPXPBS',
                ],
            ],
        ]);

        CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'original-1',
            'type' => 'original',
            'disk' => 'public',
            'path' => 'tenants/test/original.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        CloudSessionAsset::query()->create([
            'tenant_id' => $tenant->id,
            'cloud_session_id' => $session->id,
            'station_asset_id' => 'framed-1',
            'type' => 'framed',
            'disk' => 'public',
            'path' => 'tenants/test/framed.jpg',
            'mime_type' => 'image/jpeg',
            'status' => 'uploaded',
        ]);

        $this->get('/SES-NTRPXPBS')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SessionShow')
                ->where('session.code', 'SES-NTRPXPBS')
                ->where('session.share_url', route('public.sessions.show', ['sessionCode' => 'SES-NTRPXPBS']))
                ->where('session.download_all_url', route('public.sessions.download', ['sessionCode' => 'SES-NTRPXPBS']))
                ->where('session.cover_image_url', url('/storage/tenants/test/framed.jpg'))
                ->where('session.og.title', 'Gallery Foto Dafydio Photobooth')
                ->where('session.og.description', 'Lihat, download, dan cetak ulang foto kamu dari Dafydio Cloud.')
                ->where('session.og.image', url('/storage/tenants/test/framed.jpg'))
                ->where('session.og.url', route('public.sessions.show', ['sessionCode' => 'SES-NTRPXPBS']))
                ->has('assets', 2)
                ->where('assets.0.file_url', url('/storage/tenants/test/framed.jpg'))
                ->where('assets.0.download_name', 'Dafydio-Photobooth-SES-NTRPXPBS-Frame-01.jpg')
                ->where('assets.1.file_url', url('/storage/tenants/test/original.jpg'))
                ->where('assets.1.download_name', 'Dafydio-Photobooth-SES-NTRPXPBS-Original-01.jpg')
            );
    }
}
