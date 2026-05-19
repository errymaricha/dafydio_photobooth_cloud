<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_sync_logs', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->after('topic');
            $table->unique(['tenant_id', 'station_id', 'idempotency_key'], 'sync_logs_tenant_station_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('station_sync_logs', function (Blueprint $table): void {
            $table->dropUnique('sync_logs_tenant_station_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
