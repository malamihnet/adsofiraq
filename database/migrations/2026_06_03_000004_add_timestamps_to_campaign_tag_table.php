<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaign_tag') || Schema::hasColumn('campaign_tag', 'created_at')) {
            return;
        }

        Schema::table('campaign_tag', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('campaign_tag') || ! Schema::hasColumn('campaign_tag', 'created_at')) {
            return;
        }

        Schema::table('campaign_tag', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
