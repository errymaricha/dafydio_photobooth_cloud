<?php

namespace Tests\Feature;

use App\Models\CloudPrintRequest;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\CloudTemplate;
use App\Models\Customer;
use App\Models\Station;
use App\Models\Tenant;
use App\Support\StationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_station_requires_valid_token(): void
    {
        $this->postJson('/api/station/heartbeat')
            ->assertUnauthorized();

        $this->withToken('wrong-token')
            ->postJson('/api/station/heartbeat')
            ->assertUnauthorized();
    }

    public function test_station_can_send_heartbeat(): void
    {
        [$station, $token] = $this->createStation();

        $this->withToken($token)
            ->postJson('/api/station/heartbeat', [
                'device_identifier' => 'android-device-1',
                'app_version' => '1.0.0',
            ])
            ->assertOk()
            ->assertJsonPath('data.station_id', $station->id)
            ->assertJsonPath('message', 'OK');

        $this->assertSame('android-device-1', $station->refresh()->device_identifier);
        $this->assertNotNull($station->last_seen_at);
    }

    public function test_station_can_sync_published_template_to_marketplace(): void
    {
        [$station, $token] = $this->createStation();
        Storage::fake('public');

        $payload = [
            'template' => [
                'station_template_id' => 'tpl-local-001',
                'template_code' => 'WEDDING-001',
                'template_name' => 'Wedding Elegant',
                'category' => 'wedding',
                'paper_size' => '4R',
                'status' => 'published',
                'access_tier' => 'regular',
            ],
            'slots' => [
                [
                    'slot_index' => 1,
                    'x' => 120,
                    'y' => 180,
                    'width' => 800,
                    'height' => 1200,
                    'rotation' => 0,
                ],
            ],
            'assets' => [
                [
                    'station_asset_id' => 'asset-frame-001',
                    'asset_type' => 'frame',
                    'file_name' => 'wedding-frame.png',
                    'mime_type' => 'image/png',
                    'checksum' => 'sha256-frame',
                    'storage_path' => 'templates/wedding-frame.png',
                ],
                [
                    'station_asset_id' => 'asset-preview-001',
                    'asset_type' => 'preview',
                    'file_name' => 'preview.jpg',
                    'mime_type' => 'image/jpeg',
                    'storage_path' => 'templates/preview.jpg',
                ],
            ],
        ];

        $templateId = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'station:'.$station->id.':template:tpl-local-001')
            ->postJson('/api/station/sync/template', $payload)
            ->assertOk()
            ->assertJsonPath('data.station_template_id', 'tpl-local-001')
            ->assertJsonPath('data.template_code', 'WEDDING-001')
            ->assertJsonPath('data.status', 'active')
            ->json('data.cloud_template_id');

        $template = CloudTemplate::query()->findOrFail($templateId);

        $this->assertSame($station->tenant_id, $template->tenant_id);
        $this->assertSame($station->id, $template->station_id);
        $this->assertSame('marketplace', $template->access_level);
        $this->assertSame('wedding', $template->category);
        $this->assertSame('4R', $template->paper_size);
        $this->assertCount(1, $template->slots);
        $this->assertCount(2, $template->asset_manifest);
        $this->assertSame('templates/preview.jpg', $template->preview_path);
        $this->assertSame('templates/wedding-frame.png', $template->source_path);

        $registeredAsset = $this->withToken($token)
            ->postJson("/api/station/templates/{$templateId}/assets", [
                'assets' => [
                    [
                        'station_asset_id' => 'asset-preview-upload',
                        'asset_type' => 'preview',
                        'file_name' => 'preview-upload.jpg',
                        'mime_type' => 'image/jpeg',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.assets.0.status', 'pending_upload')
            ->json('data.assets.0');

        $this->withToken($token)
            ->post($registeredAsset['upload_url'], [
                'file' => UploadedFile::fake()->image('preview-upload.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'uploaded');

        $this->withToken($token)
            ->postJson("/api/station/templates/{$templateId}/assets/asset-preview-upload/complete", [
                'status' => 'completed',
                'checksum' => 'sha256-upload',
                'file_size' => 1200,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'uploaded');

        $template->refresh();
        $this->assertSame('tenants/'.$station->tenant_id.'/templates/'.$templateId.'/preview/asset-preview-upload.jpg', $template->preview_path);
        Storage::disk('public')->assertExists($template->preview_path);

        $this->withToken($token)
            ->withHeader('Idempotency-Key', 'station:'.$station->id.':template:tpl-local-001')
            ->postJson('/api/station/sync/template', $payload)
            ->assertOk()
            ->assertJsonPath('data.cloud_template_id', $templateId);

        $this->assertDatabaseHas('station_sync_logs', [
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'topic' => 'template-sync',
            'status' => 'ok',
        ]);
    }

    public function test_station_can_sync_session_assets_and_finalize(): void
    {
        [$station, $token] = $this->createStation();

        $sessionId = $this->withToken($token)
            ->postJson('/api/station/sessions', [
                'station_session_id' => 'local-session-1',
                'customer' => [
                    'whatsapp_number' => '+628111111111',
                    'name' => 'Customer Test',
                ],
                'title' => 'Event Test',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sync_status', 'syncing')
            ->json('data.cloud_session_id');

        $assetId = $this->withToken($token)
            ->postJson("/api/station/sessions/{$sessionId}/assets", [
                'assets' => [
                    [
                        'station_asset_id' => 'asset-1',
                        'type' => 'original',
                        'filename' => 'photo.jpg',
                        'mime_type' => 'image/jpeg',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.assets.0.station_asset_id', 'asset-1')
            ->json('data.assets.0.cloud_asset_id');

        $this->withToken($token)
            ->postJson("/api/station/assets/{$assetId}/complete", [
                'checksum' => 'checksum-1',
                'size_bytes' => 1200,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'uploaded');

        $this->withToken($token)
            ->postJson("/api/station/sessions/{$sessionId}/finalize", [
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJsonPath('data.sync_status', 'complete');

        $this->assertDatabaseHas('cloud_sessions', [
            'id' => $sessionId,
            'tenant_id' => $station->tenant_id,
            'sync_status' => 'complete',
        ]);

        $this->assertDatabaseHas('cloud_session_assets', [
            'id' => $assetId,
            'status' => 'uploaded',
        ]);

        $this->assertDatabaseHas('station_sync_logs', [
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'topic' => 'asset-register',
            'status' => 'ok',
        ]);

        $this->assertDatabaseHas('station_sync_logs', [
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'topic' => 'asset-complete',
            'status' => 'ok',
        ]);
    }

    public function test_station_can_register_asset_with_photo_station_field_names(): void
    {
        [$station, $token] = $this->createStation();

        $sessionId = $this->withToken($token)
            ->postJson('/api/station/sessions', [
                'station_session_id' => 'local-session-1',
                'customer' => [
                    'whatsapp_number' => '+628111111111',
                    'name' => 'Customer Test',
                ],
            ])
            ->assertCreated()
            ->json('data.cloud_session_id');

        $assetId = $this->withToken($token)
            ->postJson("/api/station/sessions/{$sessionId}/assets", [
                'assets' => [
                    [
                        'station_asset_id' => 'asset-original-1',
                        'asset_type' => 'original',
                        'file_name' => 'original-1.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 12345,
                    ],
                    [
                        'station_asset_id' => 'asset-framed-1',
                        'asset_type' => 'framed',
                        'file_name' => 'framed-1.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 23456,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.assets.0.station_asset_id', 'asset-original-1')
            ->assertJsonPath('data.assets.1.station_asset_id', 'asset-framed-1')
            ->json('data.assets.0.cloud_asset_id');

        $this->withToken($token)
            ->postJson("/api/station/assets/{$assetId}/complete", [
                'status' => 'completed',
                'checksum' => 'checksum-original',
                'file_size' => 12345,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'uploaded');

        $this->assertDatabaseHas('cloud_session_assets', [
            'station_asset_id' => 'asset-original-1',
            'type' => 'original',
            'size_bytes' => 12345,
            'status' => 'uploaded',
        ]);

        $this->assertDatabaseHas('cloud_session_assets', [
            'station_asset_id' => 'asset-framed-1',
            'type' => 'framed',
            'size_bytes' => 23456,
            'status' => 'pending_upload',
        ]);
    }

    public function test_station_can_upload_asset_file_to_public_fallback_endpoint(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.bucket' => null,
        ]);

        Storage::fake('public');

        [$station, $token] = $this->createStation();

        $sessionId = $this->withToken($token)
            ->postJson('/api/station/sessions', [
                'station_session_id' => 'local-session-1',
                'customer' => [
                    'whatsapp_number' => '+628111111111',
                    'name' => 'Customer Test',
                ],
            ])
            ->assertCreated()
            ->json('data.cloud_session_id');

        $asset = $this->withToken($token)
            ->postJson("/api/station/sessions/{$sessionId}/assets", [
                'assets' => [
                    [
                        'station_asset_id' => 'asset-original-1',
                        'asset_type' => 'original',
                        'file_name' => 'original-1.jpg',
                        'mime_type' => 'image/jpeg',
                        'file_size' => 12,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.assets.0.upload_url', url('/api/station/assets/'.$this->app['db']->table('cloud_session_assets')->value('id').'/upload'))
            ->json('data.assets.0');

        $this->call('PUT', $asset['upload_url'], [], [], [], [
            'HTTP_AUTHORIZATION' => "Bearer {$token}",
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'image/jpeg',
        ], 'fake-jpeg-binary')
            ->assertOk()
            ->assertJsonPath('message', 'Asset file received');

        $cloudAsset = CloudSessionAsset::query()->findOrFail($asset['cloud_asset_id']);

        $this->assertSame('public', $cloudAsset->disk);
        Storage::disk('public')->assertExists($cloudAsset->path);
    }

    public function test_station_can_sync_session_from_photo_station_idempotently(): void
    {
        [$station, $token] = $this->createStation();

        $payload = [
            'event' => [
                'id' => 'event-1',
                'event_code' => 'WED-001',
                'event_name' => 'Wedding Cloud',
                'cloud_upload_mode' => 'originals_and_framed',
                'cloud_member_scope' => 'regular_and_premium',
                'cloud_sync_timing' => 'after_payment',
            ],
            'session' => [
                'id' => 'station-session-1',
                'session_code' => 'S-001',
                'station_id' => 'local-station-1',
                'customer_id' => 'customer-001',
                'customer_whatsapp' => '628122222222',
                'customer_tier' => 'regular',
                'payment_status' => 'paid',
                'payment_method' => 'manual',
                'status' => 'uploaded',
                'captured_at' => '2026-05-15T03:00:00Z',
                'completed_at' => '2026-05-15T03:05:00Z',
            ],
        ];

        $headers = ['Idempotency-Key' => "station:{$station->id}:event:event-1:session:station-session-1"];

        $sessionId = $this->withToken($token)
            ->withHeaders($headers)
            ->postJson('/api/station/sync/session', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Session synced')
            ->assertJsonPath('data.sync_status', 'complete')
            ->json('data.cloud_session_id');

        $this->withToken($token)
            ->withHeaders($headers)
            ->postJson('/api/station/sync/session', $payload)
            ->assertOk()
            ->assertJsonPath('data.cloud_session_id', $sessionId);

        $this->assertDatabaseCount('cloud_sessions', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('station_sync_logs', 1);
        $this->assertDatabaseHas('cloud_sessions', [
            'id' => $sessionId,
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'station_session_id' => 'station-session-1',
            'sync_status' => 'complete',
        ]);

        $this->assertSame(
            'manual',
            CloudSession::query()->findOrFail($sessionId)->metadata['station_session']['payment_method'],
        );

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $station->tenant_id,
            'whatsapp_number' => '628122222222',
        ]);
    }

    public function test_station_can_sync_guest_session_without_creating_customer(): void
    {
        [$station, $token] = $this->createStation();

        $payload = [
            'event' => [
                'id' => 'event-guest',
                'event_code' => 'GUEST-001',
                'event_name' => 'Guest Event',
                'cloud_upload_mode' => 'originals_and_framed',
            ],
            'session' => [
                'id' => 'guest-session-1',
                'session_code' => 'SES-GUEST-001',
                'customer_whatsapp' => null,
                'customer_tier' => 'regular',
                'payment_status' => 'paid',
                'status' => 'uploaded',
            ],
        ];

        $sessionId = $this->withToken($token)
            ->withHeaders(['Idempotency-Key' => "station:{$station->id}:event:event-guest:session:guest-session-1"])
            ->postJson('/api/station/sync/session', $payload)
            ->assertOk()
            ->assertJsonPath('data.is_guest', true)
            ->assertJsonPath('data.customer_id', null)
            ->assertJsonPath('data.sync_status', 'complete')
            ->json('data.cloud_session_id');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseHas('cloud_sessions', [
            'id' => $sessionId,
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'station_session_id' => 'guest-session-1',
            'customer_id' => null,
            'sync_status' => 'complete',
        ]);

        $asset = $this->withToken($token)
            ->postJson("/api/station/sessions/{$sessionId}/assets", [
                'assets' => [
                    [
                        'station_asset_id' => 'guest-original-1',
                        'asset_type' => 'original',
                        'file_name' => 'guest.jpg',
                        'mime_type' => 'image/jpeg',
                    ],
                ],
            ])
            ->assertOk()
            ->json('data.assets.0');

        $this->assertStringContainsString('/guests/sessions/', CloudSessionAsset::query()->findOrFail($asset['cloud_asset_id'])->path);
    }

    public function test_station_can_link_guest_session_to_customer_later(): void
    {
        [$station, $token] = $this->createStation();

        $session = CloudSession::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => null,
            'station_session_id' => 'guest-session-link',
            'title' => 'Guest Link Session',
            'sync_status' => 'complete',
            'metadata' => [
                'is_guest' => true,
                'station_session' => [
                    'session_code' => 'SES-LINK-001',
                    'customer_whatsapp' => null,
                ],
            ],
        ]);

        $customerId = $this->withToken($token)
            ->postJson("/api/station/sessions/{$session->id}/link-customer", [
                'customer_whatsapp' => '6282118401998',
                'customer_name' => 'Linked Customer',
                'customer_tier' => 'regular',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Guest session linked to customer')
            ->assertJsonPath('data.customer_whatsapp', '6282118401998')
            ->assertJsonPath('data.is_guest', false)
            ->json('data.customer_id');

        $session->refresh();

        $this->assertSame($customerId, $session->customer_id);
        $this->assertFalse($session->metadata['is_guest']);
        $this->assertSame('6282118401998', $session->metadata['station_session']['customer_whatsapp']);
        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'tenant_id' => $station->tenant_id,
            'whatsapp_number' => '6282118401998',
            'name' => 'Linked Customer',
        ]);
    }

    public function test_station_session_sync_requires_idempotency_key(): void
    {
        [, $token] = $this->createStation();

        $this->withToken($token)
            ->postJson('/api/station/sync/session', [
                'event' => [
                    'id' => 'event-1',
                    'cloud_upload_mode' => 'originals_only',
                ],
                'session' => [
                    'id' => 'session-1',
                    'customer_whatsapp' => '628122222222',
                    'status' => 'uploaded',
                ],
            ])
            ->assertUnprocessable();
    }

    public function test_station_poll_print_requests_includes_asset_download_url(): void
    {
        [$station, $token] = $this->createStation();
        [$session, $asset] = $this->createUploadedAsset($station);

        $printRequest = CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 2,
            'status' => 'pending_operator',
            'priority' => '5',
            'payment_status' => 'paid',
            'metadata' => ['paper_size' => '4R'],
        ]);

        $this->withToken($token)
            ->getJson('/api/station/print-requests?status=pending&limit=25')
            ->assertOk()
            ->assertJsonPath('data.print_requests.0.id', $printRequest->id)
            ->assertJsonPath('data.print_requests.0.station_session_id', 'local-session-1')
            ->assertJsonPath('data.print_requests.0.session_code', 'SES-LOCAL-001')
            ->assertJsonPath('data.print_requests.0.copies', 2)
            ->assertJsonPath('data.print_requests.0.paper_size', '4R')
            ->assertJsonPath('data.print_requests.0.priority', '5')
            ->assertJsonPath('data.print_requests.0.payment_status', 'paid')
            ->assertJsonPath('data.print_requests.0.asset_download_url', url('/storage/tenants/test/session/photo.jpg'));
    }

    public function test_station_can_update_own_print_request_status(): void
    {
        [$station, $token] = $this->createStation();
        [$session, $asset] = $this->createUploadedAsset($station);

        $printRequest = CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 1,
            'status' => 'pending_operator',
            'priority' => 'normal',
            'payment_status' => 'paid',
        ]);

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", [
                'status' => 'claimed',
                'station_id' => 'station-local-uuid',
                'station_print_order_id' => 'print-order-uuid',
                'station_print_queue_job_id' => 'queue-job-uuid',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed')
            ->assertJsonPath('data.station_print_order_id', 'print-order-uuid');

        $this->assertNotNull($printRequest->refresh()->claimed_at);
        $this->assertSame('station-local-uuid', $printRequest->station_local_id);
        $this->assertSame('queue-job-uuid', $printRequest->station_print_queue_job_id);

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", [
                'status' => 'printing',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'printing');

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", [
                'status' => 'printed',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'printed');

        $this->assertNotNull($printRequest->refresh()->printed_at);
    }

    public function test_station_poll_print_requests_excludes_payment_pending_and_claimed_requests(): void
    {
        [$station, $token] = $this->createStation();
        [$session, $asset] = $this->createUploadedAsset($station);

        CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 1,
            'status' => 'pending_payment',
            'priority' => 'normal',
            'payment_status' => 'pending',
        ]);

        CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 1,
            'status' => 'claimed',
            'priority' => 'normal',
            'payment_status' => 'paid',
            'claimed_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson('/api/station/print-requests?status=pending&limit=25')
            ->assertOk()
            ->assertJsonCount(0, 'data.print_requests');
    }

    public function test_station_claim_print_request_is_idempotent_and_conflict_safe(): void
    {
        [$station, $token] = $this->createStation();
        [$session, $asset] = $this->createUploadedAsset($station);

        $printRequest = CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 1,
            'status' => 'pending_operator',
            'priority' => 'normal',
            'payment_status' => 'paid',
        ]);

        $claimPayload = [
            'status' => 'claimed',
            'station_id' => 'station-local-uuid',
            'station_print_order_id' => 'print-order-uuid',
            'station_print_queue_job_id' => 'queue-job-uuid',
        ];

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", $claimPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed');

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", $claimPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed');

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", [
                ...$claimPayload,
                'station_print_order_id' => 'different-print-order',
            ])
            ->assertStatus(409);
    }

    public function test_station_cannot_update_other_station_print_request(): void
    {
        [$station, $token] = $this->createStation();
        [$otherStation] = $this->createStation('other-token', 'ST-002');
        [$session, $asset] = $this->createUploadedAsset($otherStation);

        $printRequest = CloudPrintRequest::query()->create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $otherStation->id,
            'customer_id' => $session->customer_id,
            'cloud_session_id' => $session->id,
            'cloud_session_asset_id' => $asset->id,
            'quantity' => 1,
            'status' => 'pending_operator',
            'priority' => 'normal',
            'payment_status' => 'paid',
        ]);

        $this->withToken($token)
            ->patchJson("/api/station/print-requests/{$printRequest->id}", [
                'status' => 'claimed',
            ])
            ->assertNotFound();
    }

    private function createStation(string $token = 'station-token', string $code = 'ST-001'): array
    {
        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'test-tenant',
        ], [
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        $station = Station::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Station Test',
            'code' => $code,
            'api_token_hash' => Hash::make($token),
            'api_token_lookup' => StationToken::lookupHash($token),
            'status' => 'active',
        ]);

        return [$station, $token];
    }

    private function createUploadedAsset(Station $station): array
    {
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
            'metadata' => [
                'station_session' => [
                    'session_code' => 'SES-LOCAL-001',
                ],
            ],
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

        return [$session, $asset];
    }
}
