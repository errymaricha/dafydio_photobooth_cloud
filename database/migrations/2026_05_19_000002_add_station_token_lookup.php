<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->string('api_token_lookup', 64)->nullable()->after('api_token_hash');
            $table->unique('api_token_lookup', 'stations_api_token_lookup_unique');
            $table->index(['status', 'api_token_lookup'], 'stations_status_token_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table): void {
            $table->dropIndex('stations_status_token_lookup_idx');
            $table->dropUnique('stations_api_token_lookup_unique');
            $table->dropColumn('api_token_lookup');
        });
    }
};
