<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloud_sessions', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('sync_status');
            $table->timestamp('assets_deleted_at')->nullable()->after('archived_at');
            $table->index(['tenant_id', 'customer_id', 'sync_status'], 'sessions_tenant_customer_status_idx');
        });

        Schema::table('cloud_session_assets', function (Blueprint $table): void {
            $table->timestamp('deleted_at')->nullable()->after('status');
            $table->index(['tenant_id', 'status', 'deleted_at'], 'assets_tenant_status_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cloud_session_assets', function (Blueprint $table): void {
            $table->dropIndex('assets_tenant_status_deleted_idx');
            $table->dropColumn('deleted_at');
        });

        Schema::table('cloud_sessions', function (Blueprint $table): void {
            $table->dropIndex('sessions_tenant_customer_status_idx');
            $table->dropColumn(['archived_at', 'assets_deleted_at']);
        });
    }
};
