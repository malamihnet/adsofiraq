<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_person', function (Blueprint $table) {
            $table->dropUnique('campaign_person_campaign_id_person_id_role_unique');
            $table->unique(['campaign_id', 'person_id'], 'campaign_person_campaign_person_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_person', function (Blueprint $table) {
            $table->dropUnique('campaign_person_campaign_person_unique');
            $table->unique(['campaign_id', 'person_id', 'role']);
        });
    }
};
