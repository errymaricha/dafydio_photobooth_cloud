<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloud_print_requests', function (Blueprint $table): void {
            $table->string('station_local_id')->nullable()->after('payment_status');
            $table->string('station_print_order_id')->nullable()->after('station_local_id');
            $table->string('station_print_queue_job_id')->nullable()->after('station_print_order_id');
            $table->timestamp('claimed_at')->nullable()->after('station_print_queue_job_id');
            $table->timestamp('failed_at')->nullable()->after('printed_at');
            $table->text('last_error')->nullable()->after('failed_at');

            $table->index(['tenant_id', 'station_id', 'status', 'payment_status', 'created_at'], 'print_station_ready_idx');
            $table->index(['station_print_order_id'], 'print_station_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cloud_print_requests', function (Blueprint $table): void {
            $table->dropIndex('print_station_ready_idx');
            $table->dropIndex('print_station_order_idx');
            $table->dropColumn([
                'station_local_id',
                'station_print_order_id',
                'station_print_queue_job_id',
                'claimed_at',
                'failed_at',
                'last_error',
            ]);
        });
    }
};
