<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'created_at'], 'customers_tenant_status_created_idx');
            $table->index(['tenant_id', 'created_at'], 'customers_tenant_created_idx');
        });

        Schema::table('customer_subscriptions', function (Blueprint $table): void {
            $table->index(['tenant_id', 'customer_id', 'plan'], 'subs_tenant_customer_plan_idx');
            $table->index(['tenant_id', 'plan', 'status'], 'subs_tenant_plan_status_idx');
        });

        Schema::table('stations', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'created_at'], 'stations_tenant_status_created_idx');
            $table->index(['tenant_id', 'created_at'], 'stations_tenant_created_idx');
        });

        Schema::table('cloud_sessions', function (Blueprint $table): void {
            $table->index(['tenant_id', 'sync_status', 'created_at'], 'sessions_tenant_status_created_idx');
            $table->index(['tenant_id', 'created_at'], 'sessions_tenant_created_idx');
            $table->index(['tenant_id', 'customer_id', 'created_at'], 'sessions_tenant_customer_created_idx');
        });

        Schema::table('cloud_templates', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'created_at'], 'templates_tenant_status_created_idx');
            $table->index(['tenant_id', 'access_level', 'status'], 'templates_tenant_access_status_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'created_at'], 'payments_tenant_status_created_idx');
            $table->index(['tenant_id', 'customer_id', 'created_at'], 'payments_tenant_customer_created_idx');
        });

        Schema::table('station_sync_logs', function (Blueprint $table): void {
            $table->index(['tenant_id', 'created_at'], 'sync_logs_tenant_created_idx');
            $table->index(['tenant_id', 'topic', 'status', 'created_at'], 'sync_logs_tenant_topic_status_created_idx');
            $table->index(['tenant_id', 'station_id', 'created_at'], 'sync_logs_tenant_station_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('station_sync_logs', function (Blueprint $table): void {
            $table->dropIndex('sync_logs_tenant_created_idx');
            $table->dropIndex('sync_logs_tenant_topic_status_created_idx');
            $table->dropIndex('sync_logs_tenant_station_created_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_tenant_status_created_idx');
            $table->dropIndex('payments_tenant_customer_created_idx');
        });

        Schema::table('cloud_templates', function (Blueprint $table): void {
            $table->dropIndex('templates_tenant_status_created_idx');
            $table->dropIndex('templates_tenant_access_status_idx');
        });

        Schema::table('cloud_sessions', function (Blueprint $table): void {
            $table->dropIndex('sessions_tenant_status_created_idx');
            $table->dropIndex('sessions_tenant_created_idx');
            $table->dropIndex('sessions_tenant_customer_created_idx');
        });

        Schema::table('stations', function (Blueprint $table): void {
            $table->dropIndex('stations_tenant_status_created_idx');
            $table->dropIndex('stations_tenant_created_idx');
        });

        Schema::table('customer_subscriptions', function (Blueprint $table): void {
            $table->dropIndex('subs_tenant_customer_plan_idx');
            $table->dropIndex('subs_tenant_plan_status_idx');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex('customers_tenant_status_created_idx');
            $table->dropIndex('customers_tenant_created_idx');
        });
    }
};
