<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->string('alt')->nullable()->after('file_type');
            $table->string('title')->nullable()->after('alt');
            $table->string('caption')->nullable()->after('title');
            $table->unsignedSmallInteger('width')->nullable()->after('caption');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
            $table->string('mime_type', 64)->nullable()->after('height');
            $table->string('webp_path')->nullable()->after('mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_assets', function (Blueprint $table) {
            $table->dropColumn([
                'alt',
                'title',
                'caption',
                'width',
                'height',
                'mime_type',
                'webp_path',
            ]);
        });
    }
};
