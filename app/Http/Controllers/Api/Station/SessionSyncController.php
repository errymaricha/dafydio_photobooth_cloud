<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\StationSyncLog;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SessionSyncController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function syncSession(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');
        $idempotencyKey = $request->header('Idempotency-Key');

        $data = $request->validate([
            'event' => ['required', 'array'],
            'event.id' => ['required', 'string', 'max:255'],
            'event.event_code' => ['nullable', 'string', 'max:255'],
            'event.event_name' => ['nullable', 'string', 'max:255'],
            'event.cloud_upload_mode' => ['required', Rule::in(['none', 'originals_only', 'framed_only', 'originals_and_framed'])],
            'event.cloud_member_scope' => ['nullable', Rule::in(['regular_and_premium', 'premium_only', 'all_customers'])],
            'event.cloud_sync_timing' => ['nullable', Rule::in(['after_payment', 'after_session_complete', 'after_render'])],
            'session' => ['required', 'array'],
            'session.id' => ['required', 'string', 'max:255'],
            'session.session_code' => ['nullable', 'string', 'max:255'],
            'session.station_id' => ['nullable', 'string', 'max:255'],
            'session.customer_id' => ['nullable', 'string', 'max:255'],
            'session.customer_whatsapp' => ['nullable', 'string', 'max:32'],
            'session.customer_tier' => ['nullable', Rule::in(['guest', 'regular', 'premium'])],
            'session.payment_status' => ['nullable', 'string', 'max:255'],
            'session.payment_method' => ['nullable', 'string', 'max:255'],
            'session.status' => ['required', 'string', 'max:255'],
            'session.captured_at' => ['nullable', 'date'],
            'session.completed_at' => ['nullable', 'date'],
        ]);

        abort_unless(filled($idempotencyKey), 422, 'Idempotency-Key header is required.');

        $existingLog = StationSyncLog::query()
            ->where('tenant_id', $station->tenant_id)
            ->where('station_id', $station->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingLog?->response) {
            return response()->json($existingLog->response);
        }

        $customer = null;

        if (filled($data['session']['customer_whatsapp'] ?? null)) {
            $customer = Customer::query()->firstOrNew([
                'tenant_id' => $station->tenant_id,
                'whatsapp_number' => $data['session']['customer_whatsapp'],
            ]);

            $customer->fill([
                'name' => $customer->name ?: 'Customer '.$data['session']['customer_whatsapp'],
                'status' => 'active',
            ]);

            if (! $customer->exists) {
                $customer->password = Hash::make(str()->password(16));
            }

            $customer->save();

            CustomerSubscription::query()->updateOrCreate(
                [
                    'tenant_id' => $station->tenant_id,
                    'customer_id' => $customer->id,
                ],
                [
                    'plan' => ($data['session']['customer_tier'] ?? 'regular') === 'premium' ? 'premium' : 'regular',
                    'status' => 'active',
                    'starts_at' => now(),
                    'print_quota' => ($data['session']['customer_tier'] ?? 'regular') === 'premium' ? 10 : 0,
                    'storage_retention_days' => ($data['session']['customer_tier'] ?? 'regular') === 'premium' ? 365 : 30,
                ],
            );
        }

        $session = CloudSession::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'station_id' => $station->id,
                'station_session_id' => $data['session']['id'],
            ],
            [
                'customer_id' => $customer?->id,
                'title' => $data['event']['event_name'] ?? $data['session']['session_code'] ?? null,
                'sync_status' => $data['session']['status'] === 'uploaded' ? 'complete' : 'syncing',
                'metadata' => [
                    'event' => $data['event'],
                    'station_session' => $data['session'],
                    'is_guest' => blank($data['session']['customer_whatsapp'] ?? null),
                    'idempotency_key' => $idempotencyKey,
                ],
            ],
        );

        $response = [
            'data' => [
                'cloud_session_id' => $session->id,
                'customer_id' => $customer?->id,
                'is_guest' => $customer === null,
                'sync_status' => $session->sync_status,
            ],
            'meta' => [
                'idempotency_key' => $idempotencyKey,
            ],
            'message' => 'Session synced',
        ];

        StationSyncLog::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'station_id' => $station->id,
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'direction' => 'station_to_cloud',
                'topic' => 'session-sync',
                'status' => 'ok',
                'payload' => $data,
                'response' => $response,
            ],
        );

        return response()->json($response);
    }

    public function store(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');

        $data = $request->validate([
            'station_session_id' => ['required', 'string', 'max:255'],
            'customer.whatsapp_number' => ['required', 'string', 'max:32'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        $customer = Customer::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'whatsapp_number' => $data['customer']['whatsapp_number'],
            ],
            [
                'name' => $data['customer']['name'] ?? null,
                'password' => Hash::make(str()->password(16)),
                'status' => 'active',
            ],
        );

        $session = CloudSession::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'station_id' => $station->id,
                'station_session_id' => $data['station_session_id'],
            ],
            [
                'customer_id' => $customer->id,
                'title' => $data['title'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'ended_at' => $data['ended_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'sync_status' => 'syncing',
            ],
        );

        return response()->json([
            'data' => [
                'cloud_session_id' => $session->id,
                'sync_status' => $session->sync_status,
            ],
            'meta' => [],
            'message' => 'Session created',
        ], 201);
    }

    public function assets(Request $request, CloudSession $cloudSession): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($cloudSession->tenant_id === $station->tenant_id, 404);
        abort_unless($cloudSession->station_id === $station->id, 404);

        $data = $request->validate([
            'assets' => ['required', 'array', 'min:1'],
            'assets.*.station_asset_id' => ['required', 'string', 'max:255'],
            'assets.*.type' => ['nullable', Rule::in(['original', 'framed', 'edited', 'thumbnail'])],
            'assets.*.asset_type' => ['nullable', Rule::in(['original', 'framed', 'edited', 'thumbnail'])],
            'assets.*.filename' => ['nullable', 'string', 'max:255'],
            'assets.*.file_name' => ['nullable', 'string', 'max:255'],
            'assets.*.mime_type' => ['nullable', 'string', 'max:255'],
            'assets.*.size_bytes' => ['nullable', 'integer', 'min:0'],
            'assets.*.file_size' => ['nullable', 'integer', 'min:0'],
            'assets.*.checksum' => ['nullable', 'string', 'max:255'],
            'assets.*.width' => ['nullable', 'integer', 'min:1'],
            'assets.*.height' => ['nullable', 'integer', 'min:1'],
        ]);

        $assets = collect($data['assets'])->map(function (array $asset) use ($cloudSession) {
            $type = $asset['asset_type'] ?? $asset['type'] ?? null;
            $filename = $asset['file_name'] ?? $asset['filename'] ?? null;

            abort_unless($type, 422, 'Asset type is required.');
            abort_unless($filename, 422, 'Asset file name is required.');

            $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
            $ownerSegment = $cloudSession->customer_id
                ? 'customers/'.$cloudSession->customer_id
                : 'guests';

            $path = sprintf(
                'tenants/%s/%s/sessions/%s/%s/%s.%s',
                $cloudSession->tenant_id,
                $ownerSegment,
                $cloudSession->id,
                $type,
                $asset['station_asset_id'],
                $extension,
            );

            $cloudAsset = CloudSessionAsset::query()->updateOrCreate(
                [
                    'tenant_id' => $cloudSession->tenant_id,
                    'cloud_session_id' => $cloudSession->id,
                    'station_asset_id' => $asset['station_asset_id'],
                ],
                [
                    'type' => $type,
                    'disk' => $this->assetDisk(),
                    'path' => $path,
                    'mime_type' => $asset['mime_type'] ?? null,
                    'size_bytes' => $asset['file_size'] ?? $asset['size_bytes'] ?? null,
                    'checksum' => $asset['checksum'] ?? null,
                    'width' => $asset['width'] ?? null,
                    'height' => $asset['height'] ?? null,
                    'status' => 'pending_upload',
                ],
            );

            return [
                'cloud_asset_id' => $cloudAsset->id,
                'station_asset_id' => $cloudAsset->station_asset_id,
                'upload_url' => $this->assetUrlService->uploadUrl($cloudAsset, now()->addMinutes(15)),
                'status' => $cloudAsset->status,
            ];
        });

        $response = [
            'data' => ['assets' => $assets->values()],
            'meta' => [],
            'message' => 'Assets registered',
        ];

        $this->logStationSync($request, 'asset-register', 'ok', $data, $response);

        return response()->json($response);
    }

    public function completeAsset(Request $request, CloudSessionAsset $cloudAsset): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($cloudAsset->tenant_id === $station->tenant_id, 404);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['completed', 'uploaded', 'failed'])],
            'checksum' => ['nullable', 'string', 'max:255'],
            'size_bytes' => ['nullable', 'integer', 'min:0'],
            'file_size' => ['nullable', 'integer', 'min:0'],
        ]);

        abort_if(($data['status'] ?? 'completed') === 'failed', 422, 'Asset upload marked as failed.');

        $cloudAsset->update([
            'checksum' => $data['checksum'] ?? $cloudAsset->checksum,
            'size_bytes' => $data['file_size'] ?? $data['size_bytes'] ?? $cloudAsset->size_bytes,
            'status' => 'uploaded',
        ]);

        $response = [
            'data' => [
                'cloud_asset_id' => $cloudAsset->id,
                'status' => $cloudAsset->status,
            ],
            'meta' => [],
            'message' => 'Asset uploaded',
        ];

        $this->logStationSync($request, 'asset-complete', 'ok', [
            'cloud_asset_id' => $cloudAsset->id,
            ...$data,
        ], $response);

        return response()->json($response);
    }

    public function linkCustomer(Request $request, CloudSession $cloudSession): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($cloudSession->tenant_id === $station->tenant_id, 404);
        abort_unless($cloudSession->station_id === $station->id, 404);

        $data = $request->validate([
            'customer_whatsapp' => ['required', 'string', 'max:32'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_tier' => ['nullable', Rule::in(['regular', 'premium'])],
            'customer_id' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = Customer::query()->firstOrNew([
            'tenant_id' => $station->tenant_id,
            'whatsapp_number' => $data['customer_whatsapp'],
        ]);

        $customer->fill([
            'name' => $data['customer_name'] ?? $customer->name ?? 'Customer '.$data['customer_whatsapp'],
            'status' => 'active',
        ]);

        if (! $customer->exists) {
            $customer->password = Hash::make(str()->password(16));
        }

        $customer->save();

        CustomerSubscription::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'customer_id' => $customer->id,
            ],
            [
                'plan' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 'premium' : 'regular',
                'status' => 'active',
                'starts_at' => now(),
                'print_quota' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 10 : 0,
                'storage_retention_days' => ($data['customer_tier'] ?? 'regular') === 'premium' ? 365 : 30,
            ],
        );

        $metadata = $cloudSession->metadata ?? [];
        $metadata['station_session'] = [
            ...($metadata['station_session'] ?? []),
            'customer_id' => $data['customer_id'] ?? ($metadata['station_session']['customer_id'] ?? null),
            'customer_whatsapp' => $data['customer_whatsapp'],
            'customer_tier' => $data['customer_tier'] ?? ($metadata['station_session']['customer_tier'] ?? 'regular'),
        ];
        $metadata['is_guest'] = false;

        $cloudSession->update([
            'customer_id' => $customer->id,
            'metadata' => $metadata,
        ]);

        $response = [
            'data' => [
                'cloud_session_id' => $cloudSession->id,
                'customer_id' => $customer->id,
                'customer_whatsapp' => $customer->whatsapp_number,
                'is_guest' => false,
            ],
            'meta' => [],
            'message' => 'Guest session linked to customer',
        ];

        $this->logStationSync($request, 'session-link-customer', 'ok', $data, $response);

        return response()->json($response);
    }

    public function uploadAsset(Request $request, CloudSessionAsset $cloudAsset): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($cloudAsset->tenant_id === $station->tenant_id, 404);

        $contents = $request->hasFile('file')
            ? file_get_contents($request->file('file')->getRealPath())
            : $request->getContent();

        abort_unless(filled($contents), 422, 'Asset file is required.');

        Storage::disk($cloudAsset->disk)->put($cloudAsset->path, $contents);

        $response = [
            'data' => [
                'cloud_asset_id' => $cloudAsset->id,
                'status' => $cloudAsset->status,
            ],
            'meta' => [],
            'message' => 'Asset file received',
        ];

        $this->logStationSync($request, 'asset-upload', 'ok', [
            'cloud_asset_id' => $cloudAsset->id,
            'disk' => $cloudAsset->disk,
            'path' => $cloudAsset->path,
            'bytes' => strlen($contents),
        ], $response);

        return response()->json($response);
    }

    private function logStationSync(Request $request, string $topic, string $status, array $payload, array $response): void
    {
        $station = $request->attributes->get('station');
        $idempotencyKey = $request->header('Idempotency-Key');

        $attributes = [
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'idempotency_key' => $idempotencyKey,
        ];

        $values = [
            'direction' => 'station_to_cloud',
            'topic' => $topic,
            'status' => $status,
            'payload' => $payload,
            'response' => $response,
        ];

        if (filled($idempotencyKey)) {
            StationSyncLog::query()->updateOrCreate($attributes, $values);

            return;
        }

        StationSyncLog::query()->create($attributes + $values);
    }

    private function assetDisk(): string
    {
        $disk = config('filesystems.default', 'public');

        if ($disk !== 's3') {
            return $disk;
        }

        if (blank(config('filesystems.disks.s3.bucket'))) {
            return 'public';
        }

        return 's3';
    }

    public function finalize(Request $request, CloudSession $cloudSession): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($cloudSession->tenant_id === $station->tenant_id, 404);
        abort_unless($cloudSession->station_id === $station->id, 404);

        $request->validate([
            'status' => ['nullable', Rule::in(['completed', 'complete'])],
        ]);

        $cloudSession->update(['sync_status' => 'complete']);

        return response()->json([
            'data' => [
                'cloud_session_id' => $cloudSession->id,
                'sync_status' => $cloudSession->sync_status,
            ],
            'meta' => [],
            'message' => 'Session sync complete',
        ]);
    }

}
