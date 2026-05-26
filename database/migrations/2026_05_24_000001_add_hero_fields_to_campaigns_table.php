<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_hero')->default(false)->after('is_featured');
            $table->unsignedTinyInteger('hero_order')->nullable()->after('is_hero');

            $table->index(['status', 'is_hero', 'hero_order']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_hero', 'hero_order']);
            $table->dropColumn(['is_hero', 'hero_order']);
        });
    }
};
