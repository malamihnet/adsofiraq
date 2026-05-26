<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'sort_order']);
        });

        $this->migrateLegacyCampaignVideos();
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_videos');
    }

    protected function migrateLegacyCampaignVideos(): void
    {
        $campaigns = DB::table('campaigns')
            ->select(['id', 'video_url', 'video_file_path', 'video_type', 'video_provider'])
            ->where(function ($query) {
                $query->whereNotNull('video_url')
                    ->where('video_url', '!=', '')
                    ->orWhere(function ($fileQuery) {
                        $fileQuery->whereNotNull('video_file_path')
                            ->where('video_file_path', '!=', '');
                    });
            })
            ->orderBy('id')
            ->get();

        foreach ($campaigns as $campaign) {
            $type = $campaign->video_type ?: $campaign->video_provider;

            if ($type === 'file' && $campaign->video_file_path) {
                DB::table('campaign_videos')->insert([
                    'campaign_id' => $campaign->id,
                    'type' => 'file',
                    'file_path' => $campaign->video_file_path,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            if (in_array($type, ['youtube', 'vimeo'], true) && $campaign->video_url) {
                DB::table('campaign_videos')->insert([
                    'campaign_id' => $campaign->id,
                    'type' => $type,
                    'url' => $campaign->video_url,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
