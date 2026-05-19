<?php

namespace App\Actions\Customer;

use App\Models\CloudEditJob;
use App\Models\CloudSessionAsset;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RenderCloudEditJob
{
    public function handle(CloudEditJob $job): CloudEditJob
    {
        $job->loadMissing(['session', 'sourceAsset', 'template']);

        try {
            $source = $job->sourceAsset;
            $template = $job->template;

            if (! $source || ! $template) {
                throw new RuntimeException('Source photo atau template tidak ditemukan.');
            }

            if ($source->status !== 'uploaded') {
                throw new RuntimeException('Source photo belum uploaded.');
            }

            if (! $template->source_path || ! Storage::disk('public')->exists($template->source_path)) {
                throw new RuntimeException('File frame template belum tersedia di cloud.');
            }

            $sourceDisk = Storage::disk($source->disk);

            if (! $sourceDisk->exists($source->path)) {
                throw new RuntimeException('File source photo tidak ditemukan di storage.');
            }

            $rendered = $this->render(
                $sourceDisk->get($source->path),
                Storage::disk('public')->get($template->source_path),
                $template->slots ?? [],
            );

            $path = sprintf(
                'tenants/%s/sessions/%s/edited/%s.jpg',
                $job->tenant_id,
                $job->cloud_session_id,
                $job->id,
            );

            Storage::disk('public')->put($path, $rendered);

            $resultAsset = CloudSessionAsset::query()->updateOrCreate(
                [
                    'tenant_id' => $job->tenant_id,
                    'cloud_session_id' => $job->cloud_session_id,
                    'station_asset_id' => 'cloud-edit-'.$job->id,
                ],
                [
                    'type' => 'edited',
                    'disk' => 'public',
                    'path' => $path,
                    'mime_type' => 'image/jpeg',
                    'size_bytes' => strlen($rendered),
                    'checksum' => hash('sha256', $rendered),
                    'width' => $this->imageWidth($rendered),
                    'height' => $this->imageHeight($rendered),
                    'status' => 'uploaded',
                ],
            );

            $job->update([
                'result_asset_id' => $resultAsset->id,
                'status' => 'completed',
                'error_message' => null,
            ]);
        } catch (RuntimeException $exception) {
            $job->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $job->refresh();
    }

    private function render(string $sourceBytes, string $frameBytes, array $slots): string
    {
        $source = $this->imageFromBytes($sourceBytes, 'Source photo tidak bisa dibaca.');
        $frame = $this->imageFromBytes($frameBytes, 'Frame template tidak bisa dibaca.');

        $frameWidth = imagesx($frame);
        $frameHeight = imagesy($frame);
        $canvas = imagecreatetruecolor($frameWidth, $frameHeight);

        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        $slot = $slots[0] ?? [
            'x' => 0,
            'y' => 0,
            'width' => $frameWidth,
            'height' => $frameHeight,
        ];

        $this->copyCover(
            $canvas,
            $source,
            (int) round((float) ($slot['x'] ?? 0)),
            (int) round((float) ($slot['y'] ?? 0)),
            max(1, (int) round((float) ($slot['width'] ?? $frameWidth))),
            max(1, (int) round((float) ($slot['height'] ?? $frameHeight))),
        );

        imagealphablending($canvas, true);
        imagesavealpha($frame, true);
        imagecopy($canvas, $frame, 0, 0, 0, 0, $frameWidth, $frameHeight);

        ob_start();
        imagejpeg($canvas, null, 92);
        $output = ob_get_clean();

        imagedestroy($source);
        imagedestroy($frame);
        imagedestroy($canvas);

        if (! is_string($output) || $output === '') {
            throw new RuntimeException('Render image gagal dibuat.');
        }

        return $output;
    }

    private function copyCover($canvas, $source, int $dstX, int $dstY, int $dstWidth, int $dstHeight): void
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $srcRatio = $srcWidth / $srcHeight;
        $dstRatio = $dstWidth / $dstHeight;

        if ($srcRatio > $dstRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int) round($srcHeight * $dstRatio);
            $srcX = (int) round(($srcWidth - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int) round($srcWidth / $dstRatio);
            $srcX = 0;
            $srcY = (int) round(($srcHeight - $cropHeight) / 2);
        }

        imagecopyresampled($canvas, $source, $dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $cropWidth, $cropHeight);
    }

    private function imageFromBytes(string $bytes, string $message)
    {
        $image = @imagecreatefromstring($bytes);

        if (! $image) {
            throw new RuntimeException($message);
        }

        return $image;
    }

    private function imageWidth(string $bytes): ?int
    {
        $size = @getimagesizefromstring($bytes);

        return $size[0] ?? null;
    }

    private function imageHeight(string $bytes): ?int
    {
        $size = @getimagesizefromstring($bytes);

        return $size[1] ?? null;
    }
}
