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
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->index(['status', 'is_hero', 'approved_at']);
        });

        DB::table('campaigns')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_hero', 'approved_at']);
            $table->dropColumn('approved_at');
        });
    }
};
