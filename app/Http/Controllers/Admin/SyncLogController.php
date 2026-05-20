<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StationSyncLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyncLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $topic = $request->query('topic', 'all');
        $status = $request->query('status', 'all');

        $logs = StationSyncLog::query()
            ->select([
                'id',
                'tenant_id',
                'station_id',
                'direction',
                'topic',
                'idempotency_key',
                'status',
                'error_message',
                'created_at',
            ])
            ->with('station:id,name,code')
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($topic !== 'all', fn ($query) => $query->where('topic', $topic))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('topic', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('idempotency_key', 'like', $like)
                        ->orWhere('error_message', 'like', $like)
                        ->orWhere('payload', 'like', $like)
                        ->orWhere('response', 'like', $like)
                        ->orWhereHas('station', function ($query) use ($like): void {
                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('code', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/SyncLogs/Index', [
            'logs' => $logs->through(fn (StationSyncLog $log): array => [
                'id' => $log->id,
                'station_name' => $log->station?->name,
                'station_code' => $log->station?->code,
                'direction' => $log->direction,
                'topic' => $log->topic,
                'status' => $log->status,
                'idempotency_key' => $log->idempotency_key,
                'error_message' => $log->error_message,
                'detail_url' => route('admin.sync-logs.show', $log),
                'created_at' => $log->created_at?->toDateTimeString(),
            ]),
            'filters' => [
                'q' => $search,
                'topic' => $topic,
                'status' => $status,
            ],
            'topics' => StationSyncLog::query()
                ->where('tenant_id', $request->user()->tenant_id)
                ->whereNotNull('topic')
                ->distinct()
                ->orderBy('topic')
                ->pluck('topic'),
        ]);
    }

    public function show(Request $request, StationSyncLog $syncLog): Response
    {
        abort_unless($syncLog->tenant_id === $request->user()->tenant_id, 404);

        $syncLog->load('station:id,name,code');

        return Inertia::render('Admin/SyncLogs/Show', [
            'log' => [
                'id' => $syncLog->id,
                'station_name' => $syncLog->station?->name,
                'station_code' => $syncLog->station?->code,
                'direction' => $syncLog->direction,
                'topic' => $syncLog->topic,
                'status' => $syncLog->status,
                'idempotency_key' => $syncLog->idempotency_key,
                'payload' => $syncLog->payload,
                'response' => $syncLog->response,
                'error_message' => $syncLog->error_message,
                'created_at' => $syncLog->created_at?->toDateTimeString(),
                'updated_at' => $syncLog->updated_at?->toDateTimeString(),
            ],
        ]);
    }
}
