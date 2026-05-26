<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'agency_id']);
        });

        Schema::create('brand_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'brand_id']);
        });

        Schema::create('campaign_industry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'industry_id']);
        });

        Schema::create('campaign_medium_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medium_type_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'medium_type_id']);
        });

        Schema::create('campaign_country', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['campaign_id', 'country_id']);
        });

        $this->migrateLegacyForeignKeys();
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_country');
        Schema::dropIfExists('campaign_medium_type');
        Schema::dropIfExists('campaign_industry');
        Schema::dropIfExists('brand_campaign');
        Schema::dropIfExists('agency_campaign');
    }

    protected function migrateLegacyForeignKeys(): void
    {
        if (! Schema::hasColumn('campaigns', 'agency_id')) {
            return;
        }

        $now = now();

        DB::table('campaigns')->orderBy('id')->chunk(100, function ($campaigns) use ($now) {
            foreach ($campaigns as $campaign) {
                if ($campaign->agency_id) {
                    DB::table('agency_campaign')->insertOrIgnore([
                        'campaign_id' => $campaign->id,
                        'agency_id' => $campaign->agency_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if ($campaign->brand_id) {
                    DB::table('brand_campaign')->insertOrIgnore([
                        'campaign_id' => $campaign->id,
                        'brand_id' => $campaign->brand_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if ($campaign->industry_id) {
                    DB::table('campaign_industry')->insertOrIgnore([
                        'campaign_id' => $campaign->id,
                        'industry_id' => $campaign->industry_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if ($campaign->medium_type_id) {
                    DB::table('campaign_medium_type')->insertOrIgnore([
                        'campaign_id' => $campaign->id,
                        'medium_type_id' => $campaign->medium_type_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                if ($campaign->country_id) {
                    DB::table('campaign_country')->insertOrIgnore([
                        'campaign_id' => $campaign->id,
                        'country_id' => $campaign->country_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['industry_id']);
            $table->dropForeign(['medium_type_id']);
            $table->dropForeign(['country_id']);
            $table->dropColumn(['agency_id', 'brand_id', 'industry_id', 'medium_type_id', 'country_id']);
        });
    }
};
