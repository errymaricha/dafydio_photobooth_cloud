<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        CloudSessionAsset $cloudAsset,
        CloudAssetUrlService $assetUrlService,
    ): JsonResponse {
        $customer = $request->user();

        abort_unless($cloudAsset->tenant_id === $customer->tenant_id, 404);

        $session = CloudSession::query()
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->findOrFail($cloudAsset->cloud_session_id);

        abort_unless($cloudAsset->cloud_session_id === $session->id, 404);
        abort_unless($cloudAsset->status === 'uploaded', 422, 'Asset is not ready for download.');

        $expiresAt = now()->addMinutes(10);
        $downloadUrl = $assetUrlService->downloadUrl($cloudAsset, $expiresAt);

        abort_unless($downloadUrl !== null, 422, 'Download URL is not available for this storage disk.');

        return response()->json([
            'data' => [
                'download_url' => $downloadUrl,
                'expires_at' => $expiresAt->toISOString(),
            ],
            'meta' => [],
            'message' => null,
        ]);
    }
}
