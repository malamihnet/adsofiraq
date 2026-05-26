<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('role');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('slug');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('slug');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['campaigns', 'brands', 'agencies', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['verified_by']);
                $table->dropColumn(['is_verified', 'verified_at', 'verified_by']);
            });
        }
    }
};
