<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'archive_placement_enabled')) {
                $table->boolean('archive_placement_enabled')->default(false)->after('manual_order');
            }
            if (! Schema::hasColumn('campaigns', 'archive_page')) {
                $table->unsignedInteger('archive_page')->nullable()->after('archive_placement_enabled');
            }
            if (! Schema::hasColumn('campaigns', 'archive_position')) {
                $table->unsignedInteger('archive_position')->nullable()->after('archive_page');
            }

            $table->index(['archive_page', 'archive_position']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'archive_page')) {
                $table->dropIndex(['archive_page', 'archive_position']);
            }

            foreach (['archive_position', 'archive_page', 'archive_placement_enabled'] as $column) {
                if (Schema::hasColumn('campaigns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
