<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudPrintRequest;
use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\CloudTemplate;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use App\Models\Station;
use App\Models\StationSyncLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $stationsTotal = Station::query()->where('tenant_id', $tenantId)->count();
        $stationsOnline = Station::query()
            ->where('tenant_id', $tenantId)
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->count();
        $assetsTotal = CloudSessionAsset::query()->where('tenant_id', $tenantId)->count();
        $assetsUploaded = CloudSessionAsset::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'uploaded')
            ->count();
        $revenue = Payment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->sum('amount');

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'stations_online' => $stationsOnline,
                'stations_total' => $stationsTotal,
                'sessions_today' => CloudSession::query()->where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
                'sessions_total' => CloudSession::query()->where('tenant_id', $tenantId)->count(),
                'assets_uploaded' => $assetsUploaded,
                'assets_total' => $assetsTotal,
                'pending_prints' => CloudPrintRequest::query()->where('tenant_id', $tenantId)->whereIn('status', ['pending', 'pending_operator'])->count(),
                'customers' => Customer::query()->where('tenant_id', $tenantId)->count(),
                'premium_customers' => CustomerSubscription::query()->where('tenant_id', $tenantId)->where('plan', 'premium')->where('status', 'active')->count(),
                'templates' => CloudTemplate::query()->where('tenant_id', $tenantId)->count(),
                'revenue' => (float) $revenue,
                'sync_failures' => StationSyncLog::query()->where('tenant_id', $tenantId)->where('status', 'failed')->count(),
            ],
            'recentStations' => Station::query()
                ->where('tenant_id', $tenantId)
                ->latest('last_seen_at')
                ->limit(5)
                ->get()
                ->map(fn (Station $station): array => [
                    'id' => $station->id,
                    'name' => $station->name,
                    'code' => $station->code,
                    'status' => $station->status,
                    'device_identifier' => $station->device_identifier,
                    'app_version' => $station->app_version,
                    'last_seen_at' => $station->last_seen_at?->diffForHumans(),
                    'is_online' => $station->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(5)) ?? false,
                    'has_token' => filled($station->api_token_hash),
                ]),
            'recentSessions' => CloudSession::query()
                ->with(['customer:id,name,whatsapp_number', 'station:id,name'])
                ->withCount('assets')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (CloudSession $session): array => [
                    'id' => $session->id,
                    'title' => $session->title ?: 'Untitled Session',
                    'customer_name' => $session->customer?->name ?: $session->customer?->whatsapp_number,
                    'station_name' => $session->station?->name,
                    'sync_status' => $session->sync_status,
                    'assets_count' => $session->assets_count,
                    'created_at' => $session->created_at?->diffForHumans(),
                ]),
            'printRequests' => CloudPrintRequest::query()
                ->with(['customer:id,name,whatsapp_number', 'station:id,name'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (CloudPrintRequest $printRequest): array => [
                    'id' => $printRequest->id,
                    'customer_name' => $printRequest->customer?->name ?: $printRequest->customer?->whatsapp_number,
                    'station_name' => $printRequest->station?->name,
                    'quantity' => $printRequest->quantity,
                    'status' => $printRequest->status,
                    'payment_status' => $printRequest->payment_status,
                    'created_at' => $printRequest->created_at?->diffForHumans(),
                ]),
            'syncLogs' => StationSyncLog::query()
                ->with('station:id,name')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (StationSyncLog $log): array => [
                    'id' => $log->id,
                    'station_name' => $log->station?->name,
                    'direction' => $log->direction,
                    'topic' => $log->topic,
                    'status' => $log->status,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at?->diffForHumans(),
                ]),
            'storage' => [
                'default_disk' => config('filesystems.default'),
                'public_ready' => config('filesystems.default') === 'public',
                's3_configured' => filled(config('filesystems.disks.s3.key'))
                    && filled(config('filesystems.disks.s3.secret'))
                    && filled(config('filesystems.disks.s3.bucket')),
            ],
            'deployment' => [
                'queue_connection' => config('queue.default'),
                'cache_store' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'app_env' => app()->environment(),
            ],
        ]);
    }
}
