<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'source_url')) {
                $table->string('source_url', 512)->nullable()->after('admin_notes');
                $table->index('source_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'source_url')) {
                $table->dropIndex(['source_url']);
                $table->dropColumn('source_url');
            }
        });
    }
};
