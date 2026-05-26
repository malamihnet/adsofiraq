<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'purpose')) {
                $table->string('purpose', 32)->default('archive')->after('queue_order_mode');
            }
            if (! Schema::hasColumn('import_batches', 'crawl_next_page')) {
                $table->unsignedSmallInteger('crawl_next_page')->nullable()->after('crawl_max_page');
            }
            if (! Schema::hasColumn('import_batches', 'consecutive_existing')) {
                $table->unsignedInteger('consecutive_existing')->default(0)->after('crawl_next_page');
            }
            if (! Schema::hasColumn('import_batches', 'existing_skipped_count')) {
                $table->unsignedInteger('existing_skipped_count')->default(0)->after('skipped_count');
            }
            if (! Schema::hasColumn('import_batches', 'stop_after_existing')) {
                $table->unsignedInteger('stop_after_existing')->default(20)->after('consecutive_existing');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('import_batches', 'stop_after_existing')) {
                $table->dropColumn('stop_after_existing');
            }
            if (Schema::hasColumn('import_batches', 'existing_skipped_count')) {
                $table->dropColumn('existing_skipped_count');
            }
            if (Schema::hasColumn('import_batches', 'consecutive_existing')) {
                $table->dropColumn('consecutive_existing');
            }
            if (Schema::hasColumn('import_batches', 'crawl_next_page')) {
                $table->dropColumn('crawl_next_page');
            }
            if (Schema::hasColumn('import_batches', 'purpose')) {
                $table->dropColumn('purpose');
            }
        });
    }
};

