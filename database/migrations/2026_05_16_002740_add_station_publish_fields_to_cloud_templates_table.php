<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloud_templates', function (Blueprint $table): void {
            $table->foreignUlid('station_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->string('station_template_id')->nullable()->after('station_id');
            $table->string('template_code')->nullable()->after('station_template_id');
            $table->string('category')->nullable()->after('description');
            $table->string('paper_size')->nullable()->after('category');
            $table->json('slots')->nullable()->after('source_path');
            $table->json('asset_manifest')->nullable()->after('slots');
            $table->timestamp('published_at')->nullable()->after('asset_manifest');

            $table->unique(['tenant_id', 'station_id', 'station_template_id'], 'templates_tenant_station_local_unique');
            $table->index(['tenant_id', 'template_code'], 'templates_tenant_code_index');
            $table->index(['tenant_id', 'category', 'status'], 'templates_tenant_category_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('cloud_templates', function (Blueprint $table): void {
            $table->dropUnique('templates_tenant_station_local_unique');
            $table->dropIndex('templates_tenant_code_index');
            $table->dropIndex('templates_tenant_category_status_index');
            $table->dropConstrainedForeignId('station_id');
            $table->dropColumn([
                'station_template_id',
                'template_code',
                'category',
                'paper_size',
                'slots',
                'asset_manifest',
                'published_at',
            ]);
        });
    }
};
