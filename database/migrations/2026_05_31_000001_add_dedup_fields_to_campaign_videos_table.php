<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_videos', function (Blueprint $table) {
            $table->char('embed_key', 80)->nullable()->after('url');
            $table->char('source_url_key', 64)->nullable()->after('embed_key');
            $table->char('content_hash', 64)->nullable()->after('source_url_key');

            $table->index(['campaign_id', 'embed_key']);
            $table->index(['campaign_id', 'source_url_key']);
            $table->index(['campaign_id', 'content_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('campaign_videos', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'embed_key']);
            $table->dropIndex(['campaign_id', 'source_url_key']);
            $table->dropIndex(['campaign_id', 'content_hash']);
            $table->dropColumn(['embed_key', 'source_url_key', 'content_hash']);
        });
    }
};
