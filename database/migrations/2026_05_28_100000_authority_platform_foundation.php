<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $profileColumns = function (Blueprint $table) {
            $table->text('bio')->nullable()->after('slug');
            $table->string('website_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->decimal('ranking_score', 12, 4)->default(0)->index();
        };

        Schema::table('agencies', function (Blueprint $table) use ($profileColumns) {
            $profileColumns($table);
            $table->boolean('is_production_house')->default(false)->after('slug');
        });

        Schema::table('brands', function (Blueprint $table) use ($profileColumns) {
            $profileColumns($table);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('instagram_url')->nullable()->after('official_profile_url');
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('production_house')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->decimal('ranking_score', 12, 4)->default(0)->index();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('status');
            $table->boolean('needs_changes')->default(false)->after('is_draft');
            $table->string('editorial_label', 64)->nullable()->after('is_featured');
            $table->text('ai_summary')->nullable()->after('description');
            $table->boolean('is_made_by_iraq')->default(false)->after('is_student');
            $table->decimal('ranking_score', 12, 4)->default(0)->index();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft','pending','approved','rejected','needs_changes') NOT NULL DEFAULT 'pending'");
        }

        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('year');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['award_id', 'slug']);
        });

        Schema::create('award_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('placement')->default('winner');
            $table->text('jury_notes')->nullable();
            $table->timestamps();

            $table->unique(['award_category_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_winners');
        Schema::dropIfExists('award_categories');
        Schema::dropIfExists('awards');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'is_draft',
                'needs_changes',
                'editorial_label',
                'ai_summary',
                'is_made_by_iraq',
                'ranking_score',
            ]);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_url',
                'linkedin_url',
                'twitter_url',
                'production_house',
                'meta_title',
                'meta_description',
                'ranking_score',
            ]);
        });

        foreach (['agencies', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $columns = [
                    'bio',
                    'website_url',
                    'logo_path',
                    'cover_path',
                    'instagram_url',
                    'facebook_url',
                    'linkedin_url',
                    'twitter_url',
                    'founded_year',
                    'meta_title',
                    'meta_description',
                    'ranking_score',
                ];

                if ($tableName === 'agencies') {
                    $columns[] = 'is_production_house';
                }

                $table->dropColumn($columns);
            });
        }
    }
};
