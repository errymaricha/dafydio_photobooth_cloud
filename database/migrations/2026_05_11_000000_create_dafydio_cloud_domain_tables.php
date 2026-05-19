<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('business_name')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('stations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('api_token_hash')->nullable();
            $table->string('device_identifier')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'stations_tenant_code_unique');
            $table->index(['tenant_id', 'last_seen_at'], 'stations_tenant_seen_index');
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('whatsapp_number');
            $table->string('password');
            $table->timestamp('last_login_at')->nullable();
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['tenant_id', 'whatsapp_number'], 'customers_tenant_whatsapp_unique');
        });

        Schema::create('customer_subscriptions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plan')->default('regular');
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('print_quota')->default(0);
            $table->unsignedInteger('storage_retention_days')->default(30);
            $table->string('provider')->nullable();
            $table->string('provider_subscription_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cloud_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('station_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('station_session_id');
            $table->string('title')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('sync_status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'station_id', 'station_session_id'], 'sessions_tenant_station_local_unique');
            $table->index(['tenant_id', 'customer_id', 'started_at'], 'sessions_tenant_customer_started_index');
        });

        Schema::create('cloud_session_assets', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_session_id')->constrained()->cascadeOnDelete();
            $table->string('station_asset_id');
            $table->string('type');
            $table->string('disk')->default('s3');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('status')->default('pending_upload');
            $table->timestamps();

            $table->unique(['tenant_id', 'cloud_session_id', 'station_asset_id'], 'assets_tenant_session_local_unique');
            $table->index(['tenant_id', 'cloud_session_id', 'type'], 'assets_tenant_session_type_index');
        });

        Schema::create('cloud_templates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('access_level')->default('marketplace');
            $table->decimal('price_amount', 12, 2)->default(0);
            $table->string('price_currency', 3)->default('IDR');
            $table->string('preview_path')->nullable();
            $table->string('source_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->string('purpose');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider', 'provider_payment_id'], 'payments_tenant_provider_ref_index');
        });

        Schema::create('customer_template_entitlements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_template_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('purchase');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'customer_id', 'cloud_template_id'], 'entitlements_tenant_customer_template_unique');
        });

        Schema::create('cloud_edit_jobs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('source_asset_id')->constrained('cloud_session_assets')->cascadeOnDelete();
            $table->foreignUlid('cloud_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('result_asset_id')->nullable()->constrained('cloud_session_assets')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->json('editor_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('cloud_print_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('station_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_session_asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('pending');
            $table->string('priority')->default('normal');
            $table->string('payment_status')->default('not_required');
            $table->timestamp('station_claimed_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'station_id', 'status', 'created_at'], 'print_tenant_station_status_created_index');
            $table->index(['tenant_id', 'customer_id', 'created_at'], 'print_tenant_customer_created_index');
        });

        Schema::create('archive_exports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('cloud_session_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('s3');
            $table->string('path')->nullable();
            $table->string('status')->default('queued');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('station_sync_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('station_id')->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->string('topic');
            $table->string('status')->default('ok');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_sync_logs');
        Schema::dropIfExists('archive_exports');
        Schema::dropIfExists('cloud_print_requests');
        Schema::dropIfExists('cloud_edit_jobs');
        Schema::dropIfExists('customer_template_entitlements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('cloud_templates');
        Schema::dropIfExists('cloud_session_assets');
        Schema::dropIfExists('cloud_sessions');
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('stations');
        Schema::dropIfExists('tenants');
    }
};
