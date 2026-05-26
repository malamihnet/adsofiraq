<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'instagram_url')) {
                $table->string('instagram_url')->nullable()->after('website');
            }
            if (! Schema::hasColumn('users', 'tiktok_url')) {
                $table->string('tiktok_url')->nullable()->after('instagram_url');
            }
            if (! Schema::hasColumn('users', 'facebook_url')) {
                $table->string('facebook_url')->nullable()->after('tiktok_url');
            }
            if (! Schema::hasColumn('users', 'linkedin_url')) {
                $table->string('linkedin_url')->nullable()->after('facebook_url');
            }
            if (! Schema::hasColumn('users', 'username_changed_at')) {
                $table->timestamp('username_changed_at')->nullable()->after('username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['instagram_url', 'tiktok_url', 'facebook_url', 'linkedin_url', 'username_changed_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
