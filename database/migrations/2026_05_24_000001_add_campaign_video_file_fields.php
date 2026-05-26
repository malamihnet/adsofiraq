<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('video_file_path')->nullable()->after('video_provider');
            $table->string('video_type', 20)->nullable()->after('video_file_path');
        });

        DB::table('campaigns')
            ->whereNotNull('video_provider')
            ->whereIn('video_provider', ['youtube', 'vimeo'])
            ->update([
                'video_type' => DB::raw('video_provider'),
            ]);
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['video_file_path', 'video_type']);
        });
    }
};
