<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('agency_campaign', 'role')) {
            Schema::table('agency_campaign', function (Blueprint $table) {
                $table->string('role', 32)->default('agency')->after('agency_id');
            });
        }

        DB::table('agency_campaign')->whereNull('role')->update(['role' => 'agency']);

        Schema::table('agency_campaign', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['agency_id']);
        });

        Schema::table('agency_campaign', function (Blueprint $table) {
            $table->dropUnique(['campaign_id', 'agency_id']);
            $table->unique(['campaign_id', 'agency_id', 'role']);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agency_campaign', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['agency_id']);
        });

        Schema::table('agency_campaign', function (Blueprint $table) {
            $table->dropUnique(['campaign_id', 'agency_id', 'role']);
            $table->unique(['campaign_id', 'agency_id']);
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->dropColumn('role');
        });
    }
};
