<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'manual_order')) {
                $table->unsignedInteger('manual_order')->nullable()->after('hero_order');
                $table->index('manual_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'manual_order')) {
                $table->dropIndex(['manual_order']);
                $table->dropColumn('manual_order');
            }
        });
    }
};
