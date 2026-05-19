<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignUlid('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('tenant_admin')->after('password');
            $table->string('status')->default('active')->after('role');

            $table->index(['tenant_id', 'role'], 'users_tenant_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_tenant_role_index');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'status']);
        });
    }
};
