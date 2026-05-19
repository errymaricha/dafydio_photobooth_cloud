<?php

namespace App\Services\Storage;

use App\Models\CloudSessionAsset;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CloudAssetUrlService
{
    public function downloadUrl(CloudSessionAsset $asset, DateTimeInterface $expiresAt): ?string
    {
        try {
            $disk = Storage::disk($asset->disk);
        } catch (Throwable) {
            return null;
        }

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl($asset->path, $expiresAt);
            } catch (RuntimeException) {
                //
            }
        }

        if ($asset->disk === 'public') {
            return $disk->url($asset->path);
        }

        return null;
    }

    public function uploadUrl(CloudSessionAsset $asset, DateTimeInterface $expiresAt): ?string
    {
        if ($asset->disk === 'public') {
            return url("/api/station/assets/{$asset->id}/upload");
        }

        try {
            $disk = Storage::disk($asset->disk);
        } catch (Throwable) {
            return null;
        }

        if (! method_exists($disk, 'temporaryUploadUrl')) {
            return null;
        }

        try {
            return $disk->temporaryUploadUrl($asset->path, $expiresAt)['url'] ?? null;
        } catch (Throwable) {
            return null;
        }
    }
}
