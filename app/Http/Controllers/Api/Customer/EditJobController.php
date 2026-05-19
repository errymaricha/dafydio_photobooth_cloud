<?php

namespace App\Http\Controllers\Api\Customer;

use App\Actions\Customer\RenderCloudEditJob;
use App\Http\Controllers\Controller;
use App\Models\CloudEditJob;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\CloudTemplate;
use App\Models\CustomerSubscription;
use App\Models\CustomerTemplateEntitlement;
use App\Services\Storage\CloudAssetUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditJobController extends Controller
{
    public function __construct(private readonly CloudAssetUrlService $assetUrlService) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $jobs = CloudEditJob::query()
            ->with(['session', 'sourceAsset', 'template', 'resultAsset'])
            ->where('tenant_id', $customer->tenant_id)
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($jobs->items())->map(fn (CloudEditJob $job): array => [
                'id' => $job->id,
                'cloud_session_id' => $job->cloud_session_id,
                'source_asset_id' => $job->source_asset_id,
                'cloud_template_id' => $job->cloud_template_id,
                'result_asset_id' => $job->result_asset_id,
                'result_asset' => $job->resultAsset ? [
                    'id' => $job->resultAsset->id,
                    'type' => $job->resultAsset->type,
                    'file_url' => $job->resultAsset->status === 'uploaded'
                        ? $this->assetUrlService->downloadUrl($job->resultAsset, now()->addMinutes(30))
                        : null,
                ] : null,
                'status' => $job->status,
                'error_message' => $job->error_message,
                'created_at' => $job->created_at?->toDateTimeString(),
                'session_title' => $job->session?->title,
                'template_name' => $job->template?->name,
                'source_asset_type' => $job->sourceAsset?->type,
            ])->values(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'total' => $jobs->total(),
            ],
            'message' => null,
        ]);
    }

    public function store(Request $request, RenderCloudEditJob $renderer): JsonResponse
    {
        $customer = $request->user();

        $data = $request->validate([
            'cloud_session_id' => ['required', 'exists:cloud_sessions,id'],
            'source_asset_id' => ['required', 'exists:cloud_session_assets,id'],
            'cloud_template_id' => ['nullable', 'exists:cloud_templates,id'],
            'editor_payload' => ['nullable', 'array'],
        ]);

        $session = CloudSession::query()->findOrFail($data['cloud_session_id']);
        $asset = CloudSessionAsset::query()->findOrFail($data['source_asset_id']);
        $template = isset($data['cloud_template_id'])
            ? CloudTemplate::query()->findOrFail($data['cloud_template_id'])
            : null;

        abort_unless($session->tenant_id === $customer->tenant_id && $session->customer_id === $customer->id, 404);
        abort_unless($asset->tenant_id === $customer->tenant_id && $asset->cloud_session_id === $session->id, 422);

        if ($template) {
            abort_unless($template->tenant_id === $customer->tenant_id && $template->status === 'active', 404);

            $hasEntitlement = CustomerTemplateEntitlement::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->where('cloud_template_id', $template->id)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();

            $hasPremium = CustomerSubscription::query()
                ->where('tenant_id', $customer->tenant_id)
                ->where('customer_id', $customer->id)
                ->where('plan', 'premium')
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                ->exists();

            abort_unless($hasEntitlement || ($template->access_level === 'premium' && $hasPremium), 403);
        }

        $job = CloudEditJob::query()->create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'cloud_session_id' => $session->id,
            'source_asset_id' => $asset->id,
            'cloud_template_id' => $template?->id,
            'status' => 'queued',
            'editor_payload' => $data['editor_payload'] ?? [],
        ]);

        $job = $renderer->handle($job);

        return response()->json([
            'data' => [
                'edit_job_id' => $job->id,
                'result_asset_id' => $job->result_asset_id,
                'status' => $job->status,
                'error_message' => $job->error_message,
            ],
            'meta' => [],
            'message' => $job->status === 'completed' ? 'Edit job completed' : 'Edit job created',
        ], 201);
    }
}
