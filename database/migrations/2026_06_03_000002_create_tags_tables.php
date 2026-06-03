<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('source')->nullable();
            $table->unsignedInteger('campaigns_count')->default(0);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('campaign_tag', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['campaign_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_tag');
        Schema::dropIfExists('tags');
    }
};
