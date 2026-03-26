<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discovered_apps', function (Blueprint $table) {
            $table->text('experience_fr')->nullable()->after('summary_fr');
            $table->text('retention_why_fr')->nullable()->after('retention_estimate');
            $table->text('usage_detail_fr')->nullable()->after('usage_duration');
            $table->text('competition_detail_fr')->nullable()->after('competition_level');
            $table->text('features_list_fr')->nullable()->after('feature_count');
            $table->string('age_group')->nullable()->after('target_audience_fr');
        });
    }

    public function down(): void
    {
        Schema::table('discovered_apps', function (Blueprint $table) {
            $table->dropColumn([
                'experience_fr', 'retention_why_fr', 'usage_detail_fr',
                'competition_detail_fr', 'features_list_fr', 'age_group',
            ]);
        });
    }
};
