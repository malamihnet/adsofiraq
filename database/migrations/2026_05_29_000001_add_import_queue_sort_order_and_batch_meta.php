<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('import_batches', 'crawl_max_page')) {
                $table->unsignedSmallInteger('crawl_max_page')->nullable()->after('total_urls');
            }
            if (! Schema::hasColumn('import_batches', 'queue_order_mode')) {
                $table->string('queue_order_mode', 32)->default('oldest_first')->after('crawl_max_page');
            }
        });

        Schema::table('import_queue', function (Blueprint $table) {
            if (! Schema::hasColumn('import_queue', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('page_number');
            }
        });

        Schema::table('import_queue', function (Blueprint $table) {
            $table->index(['batch_id', 'status', 'sort_order'], 'import_queue_batch_status_sort_idx');
        });

        $this->backfillSortOrder();
    }

    public function down(): void
    {
        Schema::table('import_queue', function (Blueprint $table) {
            $table->dropIndex('import_queue_batch_status_sort_idx');
            if (Schema::hasColumn('import_queue', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });

        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('import_batches', 'queue_order_mode')) {
                $table->dropColumn('queue_order_mode');
            }
            if (Schema::hasColumn('import_batches', 'crawl_max_page')) {
                $table->dropColumn('crawl_max_page');
            }
        });
    }

    protected function backfillSortOrder(): void
    {
        if (! Schema::hasColumn('import_queue', 'sort_order')) {
            return;
        }

        $batchIds = DB::table('import_queue')->distinct()->pluck('batch_id');

        foreach ($batchIds as $batchId) {
            $items = DB::table('import_queue')
                ->where('batch_id', $batchId)
                ->orderByDesc('page_number')
                ->orderBy('id')
                ->get(['id']);

            foreach ($items as $index => $item) {
                DB::table('import_queue')
                    ->where('id', $item->id)
                    ->update(['sort_order' => $index]);
            }
        }
    }
};
