<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_apps', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary_fr')->nullable();
            $table->string('category')->nullable();
            $table->string('platform'); // ios, android, pwa, web
            $table->date('release_date')->nullable();
            $table->string('source'); // producthunt, apple_rss, google_play, hackernews, indiehackers
            $table->string('source_url')->nullable();
            $table->string('icon_url')->nullable();

            // Analyse Claude API
            $table->unsignedSmallInteger('feature_count')->nullable();
            $table->text('exceptional_factor_fr')->nullable();
            $table->text('target_audience_fr')->nullable();
            $table->string('business_model')->nullable(); // freemium, subscription, ads, paid, free
            $table->text('pros_fr')->nullable(); // JSON array
            $table->text('cons_fr')->nullable(); // JSON array
            $table->unsignedSmallInteger('explosion_score')->nullable(); // /10
            $table->text('explosion_verdict_fr')->nullable();
            $table->unsignedSmallInteger('buzz_score')->nullable(); // /10
            $table->string('retention_estimate')->nullable(); // faible, moyenne, forte
            $table->unsignedSmallInteger('k_factor')->nullable(); // /10
            $table->text('sharing_mechanisms_fr')->nullable();
            $table->boolean('group_belonging')->nullable();
            $table->text('group_belonging_detail_fr')->nullable();
            $table->string('usage_duration')->nullable(); // courte, moyenne, longue
            $table->boolean('user_recognition')->nullable();
            $table->text('user_recognition_detail_fr')->nullable();
            $table->string('competition_level')->nullable(); // faible, moyenne, saturée
            $table->string('technical_effort')->nullable(); // facile, moyen, complexe
            $table->string('market_size')->nullable(); // niche, moyen, massif

            // Statut
            $table->boolean('analyzed')->default(false);
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();

            // Index pour le tri par défaut
            $table->index('explosion_score');
            $table->index('buzz_score');
            $table->index('category');
            $table->index('platform');
            $table->index('source');
            $table->index('release_date');
            $table->index('analyzed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_apps');
    }
};
