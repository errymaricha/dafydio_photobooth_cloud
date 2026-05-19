<?php

namespace App\Http\Controllers\Api\Station;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use App\Models\StationSyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TemplateSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $station = $request->attributes->get('station');
        $idempotencyKey = $request->header('Idempotency-Key');

        abort_unless(filled($idempotencyKey), 422, 'Idempotency-Key header is required.');

        $existingLog = StationSyncLog::query()
            ->where('tenant_id', $station->tenant_id)
            ->where('station_id', $station->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingLog?->response) {
            return response()->json($existingLog->response);
        }

        $data = $request->validate([
            'template' => ['required', 'array'],
            'template.station_template_id' => ['required', 'string', 'max:255'],
            'template.template_code' => ['nullable', 'string', 'max:255'],
            'template.template_name' => ['required', 'string', 'max:255'],
            'template.category' => ['nullable', 'string', 'max:255'],
            'template.paper_size' => ['nullable', 'string', 'max:255'],
            'template.status' => ['required', Rule::in(['local_only', 'publish_to_cloud', 'published', 'draft', 'active', 'archived'])],
            'template.access_tier' => ['nullable', Rule::in(['regular', 'premium', 'private', 'marketplace'])],
            'template.description' => ['nullable', 'string'],
            'template.price_amount' => ['nullable', 'numeric', 'min:0'],
            'template.price_currency' => ['nullable', 'string', 'size:3'],
            'slots' => ['nullable', 'array'],
            'slots.*.slot_index' => ['required_with:slots', 'integer', 'min:1'],
            'slots.*.x' => ['required_with:slots', 'numeric'],
            'slots.*.y' => ['required_with:slots', 'numeric'],
            'slots.*.width' => ['required_with:slots', 'numeric', 'min:1'],
            'slots.*.height' => ['required_with:slots', 'numeric', 'min:1'],
            'slots.*.rotation' => ['nullable', 'numeric'],
            'assets' => ['nullable', 'array'],
            'assets.*.station_asset_id' => ['required_with:assets', 'string', 'max:255'],
            'assets.*.asset_type' => ['required_with:assets', Rule::in(['frame', 'preview', 'source', 'overlay', 'font', 'other'])],
            'assets.*.file_name' => ['required_with:assets', 'string', 'max:255'],
            'assets.*.mime_type' => ['nullable', 'string', 'max:255'],
            'assets.*.file_size' => ['nullable', 'integer', 'min:0'],
            'assets.*.checksum' => ['nullable', 'string', 'max:255'],
            'assets.*.storage_path' => ['nullable', 'string', 'max:255'],
            'assets.*.url' => ['nullable', 'string', 'max:255'],
        ]);

        $templateData = $data['template'];
        $assets = collect($data['assets'] ?? []);
        $previewAsset = $assets->firstWhere('asset_type', 'preview');
        $sourceAsset = $assets->firstWhere('asset_type', 'source') ?? $assets->firstWhere('asset_type', 'frame');

        $template = CloudTemplate::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'station_id' => $station->id,
                'station_template_id' => $templateData['station_template_id'],
            ],
            [
                'template_code' => $templateData['template_code'] ?? null,
                'name' => $templateData['template_name'],
                'description' => $templateData['description'] ?? null,
                'category' => $templateData['category'] ?? null,
                'paper_size' => $templateData['paper_size'] ?? null,
                'access_level' => $this->accessLevel($templateData['access_tier'] ?? 'regular'),
                'price_amount' => $templateData['price_amount'] ?? 0,
                'price_currency' => $templateData['price_currency'] ?? 'IDR',
                'preview_path' => $this->assetPath($previewAsset),
                'source_path' => $this->assetPath($sourceAsset),
                'slots' => $data['slots'] ?? [],
                'asset_manifest' => $assets->values()->all(),
                'status' => $this->status($templateData['status']),
                'published_at' => in_array($templateData['status'], ['published', 'active'], true) ? now() : null,
            ],
        );

        $response = [
            'data' => [
                'cloud_template_id' => $template->id,
                'station_template_id' => $template->station_template_id,
                'template_code' => $template->template_code,
                'status' => $template->status,
            ],
            'meta' => [
                'idempotency_key' => $idempotencyKey,
            ],
            'message' => 'Template synced',
        ];

        StationSyncLog::query()->updateOrCreate(
            [
                'tenant_id' => $station->tenant_id,
                'station_id' => $station->id,
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'direction' => 'inbound',
                'topic' => 'template-sync',
                'status' => 'ok',
                'request' => $data,
                'response' => $response,
            ],
        );

        return response()->json($response);
    }

    public function assets(Request $request, CloudTemplate $template): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($template->tenant_id === $station->tenant_id && $template->station_id === $station->id, 404);

        $data = $request->validate([
            'assets' => ['required', 'array', 'min:1'],
            'assets.*.station_asset_id' => ['required', 'string', 'max:255'],
            'assets.*.asset_type' => ['required', Rule::in(['frame', 'preview', 'source', 'overlay', 'font', 'other'])],
            'assets.*.file_name' => ['required', 'string', 'max:255'],
            'assets.*.mime_type' => ['nullable', 'string', 'max:255'],
            'assets.*.file_size' => ['nullable', 'integer', 'min:0'],
            'assets.*.checksum' => ['nullable', 'string', 'max:255'],
        ]);

        $manifest = collect($template->asset_manifest ?? []);

        $assets = collect($data['assets'])->map(function (array $asset) use ($template, &$manifest): array {
            $extension = pathinfo($asset['file_name'], PATHINFO_EXTENSION) ?: 'bin';
            $path = sprintf(
                'tenants/%s/templates/%s/%s/%s.%s',
                $template->tenant_id,
                $template->id,
                $asset['asset_type'],
                $asset['station_asset_id'],
                $extension,
            );

            $manifest = $manifest
                ->reject(fn (array $item): bool => ($item['station_asset_id'] ?? null) === $asset['station_asset_id'])
                ->push($asset + [
                    'storage_path' => $path,
                    'status' => 'pending_upload',
                ])
                ->values();

            return [
                'station_asset_id' => $asset['station_asset_id'],
                'asset_type' => $asset['asset_type'],
                'upload_url' => url("/api/station/templates/{$template->id}/assets/{$asset['station_asset_id']}/upload"),
                'status' => 'pending_upload',
            ];
        });

        $template->update(['asset_manifest' => $manifest->all()]);

        return response()->json([
            'data' => ['assets' => $assets->values()],
            'meta' => [],
            'message' => 'Template assets registered',
        ]);
    }

    public function uploadAsset(Request $request, CloudTemplate $template, string $stationAssetId): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($template->tenant_id === $station->tenant_id && $template->station_id === $station->id, 404);

        $manifest = collect($template->asset_manifest ?? []);
        $asset = $manifest->firstWhere('station_asset_id', $stationAssetId);

        abort_unless($asset && isset($asset['storage_path']), 404, 'Template asset is not registered.');

        $contents = $request->file('file')
            ? file_get_contents($request->file('file')->getRealPath())
            : $request->getContent();

        abort_unless($contents !== false && $contents !== '', 422, 'Asset file body is required.');

        Storage::disk('public')->put($asset['storage_path'], $contents);

        return response()->json([
            'data' => [
                'station_asset_id' => $stationAssetId,
                'status' => 'uploaded',
                'storage_path' => $asset['storage_path'],
                'file_url' => Storage::disk('public')->url($asset['storage_path']),
            ],
            'meta' => [],
            'message' => 'Template asset uploaded',
        ]);
    }

    public function completeAsset(Request $request, CloudTemplate $template, string $stationAssetId): JsonResponse
    {
        $station = $request->attributes->get('station');

        abort_unless($template->tenant_id === $station->tenant_id && $template->station_id === $station->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['completed', 'uploaded', 'failed'])],
            'checksum' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
        ]);

        $manifest = collect($template->asset_manifest ?? []);
        $targetAsset = null;

        $manifest = $manifest->map(function (array $asset) use ($stationAssetId, $data, &$targetAsset): array {
            if (($asset['station_asset_id'] ?? null) !== $stationAssetId) {
                return $asset;
            }

            $targetAsset = $asset + $data;

            return array_merge($targetAsset, [
                'status' => $data['status'] === 'failed' ? 'failed' : 'uploaded',
            ]);
        });

        abort_unless($targetAsset, 404, 'Template asset is not registered.');

        $updates = ['asset_manifest' => $manifest->values()->all()];

        if (($targetAsset['asset_type'] ?? null) === 'preview') {
            $updates['preview_path'] = $targetAsset['storage_path'] ?? null;
        }

        if (in_array($targetAsset['asset_type'] ?? null, ['frame', 'source'], true)) {
            $updates['source_path'] = $targetAsset['storage_path'] ?? null;
        }

        $template->update($updates);

        return response()->json([
            'data' => [
                'station_asset_id' => $stationAssetId,
                'status' => $data['status'] === 'failed' ? 'failed' : 'uploaded',
                'storage_path' => $targetAsset['storage_path'] ?? null,
                'file_url' => isset($targetAsset['storage_path']) ? Storage::disk('public')->url($targetAsset['storage_path']) : null,
            ],
            'meta' => [],
            'message' => 'Template asset completed',
        ]);
    }

    private function accessLevel(string $accessTier): string
    {
        return match ($accessTier) {
            'premium' => 'premium',
            'private' => 'private',
            default => 'marketplace',
        };
    }

    private function status(string $status): string
    {
        return match ($status) {
            'local_only', 'draft' => 'draft',
            'archived' => 'archived',
            default => 'active',
        };
    }

    private function assetPath(?array $asset): ?string
    {
        if (! $asset) {
            return null;
        }

        return $asset['url'] ?? $asset['storage_path'] ?? null;
    }
}
