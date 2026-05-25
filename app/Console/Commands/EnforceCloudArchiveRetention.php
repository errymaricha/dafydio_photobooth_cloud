<?php

namespace App\Console\Commands;

use App\Models\CloudSession;
use App\Models\CloudSessionAsset;
use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EnforceCloudArchiveRetention extends Command
{
    protected $signature = 'cloud:enforce-retention
        {--archive-months=3 : Archive customers whose newest session is older than this many months}
        {--delete-months=5 : Delete archived customer asset files whose newest session is older than this many months}
        {--dry-run : Show what would change without writing to database or deleting files}';

    protected $description = 'Archive inactive customer sessions and delete old archived cloud asset files.';

    public function handle(): int
    {
        $archiveMonths = max(1, (int) $this->option('archive-months'));
        $deleteMonths = max($archiveMonths, (int) $this->option('delete-months'));
        $dryRun = (bool) $this->option('dry-run');
        $archiveBefore = now()->subMonths($archiveMonths);
        $deleteBefore = now()->subMonths($deleteMonths);

        $archivedCustomers = 0;
        $archivedSessions = 0;
        $deletedCustomers = 0;
        $deletedSessions = 0;
        $deletedAssets = 0;

        $this->info(sprintf(
            'Cloud retention started. Archive before %s, delete before %s%s.',
            $archiveBefore->toDateString(),
            $deleteBefore->toDateString(),
            $dryRun ? ' (dry run)' : ''
        ));

        $this->inactiveCustomers($archiveBefore)
            ->chunkById(100, function ($customers) use ($dryRun, &$archivedCustomers, &$archivedSessions): void {
                foreach ($customers as $customer) {
                    $query = CloudSession::query()
                        ->where('tenant_id', $customer->tenant_id)
                        ->where('customer_id', $customer->id)
                        ->whereNotIn('sync_status', ['archived', 'deleted']);

                    $count = (clone $query)->count();

                    if ($count < 1) {
                        continue;
                    }

                    $archivedCustomers++;
                    $archivedSessions += $count;

                    if (! $dryRun) {
                        $query->update([
                            'sync_status' => 'archived',
                            'archived_at' => now(),
                        ]);
                    }
                }
            });

        $this->inactiveCustomers($deleteBefore)
            ->chunkById(100, function ($customers) use ($dryRun, &$deletedCustomers, &$deletedSessions, &$deletedAssets): void {
                foreach ($customers as $customer) {
                    $sessions = CloudSession::query()
                        ->where('tenant_id', $customer->tenant_id)
                        ->where('customer_id', $customer->id)
                        ->where('sync_status', 'archived')
                        ->whereNull('assets_deleted_at')
                        ->get();

                    if ($sessions->isEmpty()) {
                        continue;
                    }

                    $deletedCustomers++;
                    $deletedSessions += $sessions->count();

                    foreach ($sessions as $session) {
                        $deletedAssets += $this->deleteSessionAssets($session, $dryRun);

                        if (! $dryRun) {
                            $session->update([
                                'sync_status' => 'deleted',
                                'assets_deleted_at' => now(),
                            ]);
                        }
                    }
                }
            });

        $this->components->info("Archived customers: {$archivedCustomers}; sessions: {$archivedSessions}.");
        $this->components->info("Deleted customers: {$deletedCustomers}; sessions: {$deletedSessions}; asset files: {$deletedAssets}.");

        return self::SUCCESS;
    }

    private function inactiveCustomers($before)
    {
        return Customer::query()
            ->whereIn('id', CloudSession::query()
                ->select('customer_id')
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->havingRaw('MAX(COALESCE(started_at, created_at)) <= ?', [$before]))
            ->orderBy('id');
    }

    private function deleteSessionAssets(CloudSession $session, bool $dryRun): int
    {
        $count = 0;

        CloudSessionAsset::query()
            ->where('tenant_id', $session->tenant_id)
            ->where('cloud_session_id', $session->id)
            ->where('status', '!=', 'deleted')
            ->chunkById(100, function ($assets) use ($dryRun, &$count): void {
                foreach ($assets as $asset) {
                    $count++;

                    if ($dryRun) {
                        continue;
                    }

                    if ($asset->path) {
                        Storage::disk($asset->disk)->delete($asset->path);
                    }

                    DB::table('cloud_session_assets')
                        ->where('id', $asset->id)
                        ->update([
                            'status' => 'deleted',
                            'deleted_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            });

        return $count;
    }
}
