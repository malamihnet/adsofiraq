<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('medium_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->date('published_at')->nullable();
            $table->longText('description');
            $table->longText('credits')->nullable();
            $table->string('video_url')->nullable();
            $table->enum('video_provider', ['youtube', 'vimeo'])->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_student')->default(false);
            $table->boolean('is_nsfw')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->text('admin_notes')->nullable();
            $table->text('submission_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['is_featured', 'status']);
        });

        Schema::create('campaign_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type')->default('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_assets');
        Schema::dropIfExists('campaigns');
    }
};
