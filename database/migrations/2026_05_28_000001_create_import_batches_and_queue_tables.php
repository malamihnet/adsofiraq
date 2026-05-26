<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country_url', 512);
            $table->string('status', 32)->default('queued');
            $table->unsignedInteger('total_urls')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('import_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id');
            $table->string('url', 512);
            $table->string('status', 32)->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('page_number')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('import_batches')->cascadeOnDelete();
            $table->index(['batch_id', 'status']);
            $table->index('url');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'source_batch_id')) {
                $table->uuid('source_batch_id')->nullable()->after('source_url');
                $table->index('source_batch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'source_batch_id')) {
                $table->dropIndex(['source_batch_id']);
                $table->dropColumn('source_batch_id');
            }
        });

        Schema::dropIfExists('import_queue');
        Schema::dropIfExists('import_batches');
    }
};
