<?php

namespace App\Http\Controllers;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use ZipArchive;

class PublicSessionController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function show(string $sessionCode): Response|SymfonyResponse
    {
        $session = $this->findSession($sessionCode);

        if (! $session) {
            return Inertia::render('Public/SessionNotFound', [
                'sessionCode' => $sessionCode,
                'homeUrl' => url('/'),
            ])->toResponse(request())->setStatusCode(404);
        }

        $assets = $this->publicAssets($session, $sessionCode);
        $shareUrl = route('public.sessions.show', ['sessionCode' => $sessionCode]);
        $coverImageUrl = $assets->firstWhere('type', 'framed')['file_url']
            ?? $assets->first()['file_url']
            ?? url('/images/dafydio-booth-icon.png');
        $sessionTitle = $session->title ?: 'Gallery Foto';
        $ogTitle = "{$sessionTitle} - {$sessionCode} | Dafydio Photobooth";
        $ogDescription = "Lihat, download, dan cetak ulang foto session {$sessionCode} dari Dafydio Cloud.";

        return Inertia::render('Public/SessionShow', [
            'session' => [
                'id' => $session->id,
                'code' => $sessionCode,
                'title' => $session->title ?: 'Photobooth Session',
                'sync_status' => $session->sync_status,
                'created_at' => $session->created_at?->toDateTimeString(),
                'station_name' => $session->station?->name,
                'share_url' => $shareUrl,
                'download_all_url' => route('public.sessions.download', ['sessionCode' => $sessionCode]),
                'cover_image_url' => $coverImageUrl,
                'og' => [
                    'title' => $ogTitle,
                    'description' => $ogDescription,
                    'image' => $coverImageUrl,
                    'url' => $shareUrl,
                    'canonical' => $shareUrl,
                ],
            ],
            'assets' => $assets,
        ]);
    }

    public function downloadAll(string $sessionCode): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'ZIP download is not available on this server.');

        $session = $this->findSession($sessionCode);
        abort_if(! $session, 404, 'Gallery code was not found.');

        $assets = $this->publicAssets($session, $sessionCode);

        abort_if($assets->isEmpty(), 404, 'No uploaded photos are available.');

        $zipDirectory = storage_path('app/private/public-gallery-zips');

        if (! is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zipPath = $zipDirectory.'/'.$this->safeSessionCode($sessionCode).'-'.uniqid().'.zip';
        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500, 'Unable to create ZIP file.');

        $assets->each(function (array $asset) use ($zip): void {
            $model = CloudSessionAsset::query()->find($asset['id']);

            if (! $model) {
                return;
            }

            $disk = Storage::disk($model->disk);

            if (! $disk->exists($model->path)) {
                return;
            }

            $zip->addFromString($asset['download_name'], $disk->get($model->path));
        });

        $zip->close();

        return response()->download(
            $zipPath,
            'Dafydio-Photobooth-'.$this->safeSessionCode($sessionCode).'-All-Photos.zip',
        )->deleteFileAfterSend(true);
    }

    private function findSession(string $sessionCode): ?CloudSession
    {
        return CloudSession::query()
            ->with(['assets', 'customer', 'station'])
            ->where('station_session_id', $sessionCode)
            ->orWhere('metadata->station_session->session_code', $sessionCode)
            ->latest()
            ->first();
    }

    private function publicAssets(CloudSession $session, string $sessionCode): Collection
    {
        $assetNumbers = [];

        return $session->assets
            ->where('status', 'uploaded')
            ->whereIn('type', ['original', 'framed'])
            ->sortBy('type')
            ->values()
            ->map(function ($asset) use (&$assetNumbers, $sessionCode): array {
                $assetNumbers[$asset->type] = ($assetNumbers[$asset->type] ?? 0) + 1;

                return [
                    'id' => $asset->id,
                    'type' => $asset->type,
                    'station_asset_id' => $asset->station_asset_id,
                    'mime_type' => $asset->mime_type,
                    'size_bytes' => $asset->size_bytes,
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'file_url' => $this->assetUrlService->downloadUrl($asset, now()->addMinutes(30)),
                    'download_name' => $this->downloadName($sessionCode, $asset->type, $assetNumbers[$asset->type], $asset->path),
                ];
            });
    }

    private function downloadName(string $sessionCode, string $type, int $number, ?string $path): string
    {
        $extension = pathinfo($path ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        $label = $type === 'framed' ? 'Frame' : 'Original';
        $safeCode = $this->safeSessionCode($sessionCode);

        return sprintf('Dafydio-Photobooth-%s-%s-%02d.%s', $safeCode, $label, $number, $extension);
    }

    private function safeSessionCode(string $sessionCode): string
    {
        return preg_replace('/[^A-Za-z0-9-]+/', '-', $sessionCode) ?: 'Session';
    }
}
