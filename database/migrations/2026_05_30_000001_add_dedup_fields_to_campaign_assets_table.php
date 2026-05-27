<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->text('source_url')->nullable()->after('file_type');
            $table->char('source_url_key', 64)->nullable()->after('source_url');
            $table->char('content_hash', 64)->nullable()->after('source_url_key');

            $table->index(['campaign_id', 'content_hash']);
            $table->index(['campaign_id', 'source_url_key']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'content_hash']);
            $table->dropIndex(['campaign_id', 'source_url_key']);
            $table->dropColumn(['source_url', 'source_url_key', 'content_hash']);
        });
    }
};
