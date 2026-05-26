<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'campaign_id']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedInteger('watchers_count')->default(0)->after('bookmarks_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_watchers');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('watchers_count');
        });
    }
};
