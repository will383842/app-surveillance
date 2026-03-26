<?php

namespace App\Services\Scrapers;

use App\Models\DiscoveredApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlayScraper
{
    /**
     * Utilise un scraper Node.js open-source (google-play-scraper)
     * exposé via un micro-service local, ou directement les données
     * depuis le endpoint interne.
     *
     * Alternative: scrape la page "Nouvelles apps" de Google Play.
     */

    private const CATEGORIES = [
        'APPLICATION', 'GAME', 'ART_AND_DESIGN', 'AUTO_AND_VEHICLES',
        'BEAUTY', 'BOOKS_AND_REFERENCE', 'BUSINESS', 'COMICS',
        'COMMUNICATION', 'DATING', 'EDUCATION', 'ENTERTAINMENT',
        'EVENTS', 'FINANCE', 'FOOD_AND_DRINK', 'HEALTH_AND_FITNESS',
        'HOUSE_AND_HOME', 'LIFESTYLE', 'MAPS_AND_NAVIGATION',
        'MEDICAL', 'MUSIC_AND_AUDIO', 'NEWS_AND_MAGAZINES',
        'PARENTING', 'PERSONALIZATION', 'PHOTOGRAPHY',
        'PRODUCTIVITY', 'SHOPPING', 'SOCIAL', 'SPORTS',
        'TOOLS', 'TRAVEL_AND_LOCAL', 'VIDEO_PLAYERS', 'WEATHER',
    ];

    private const COUNTRIES = ['us', 'fr', 'de', 'gb', 'jp', 'br', 'in', 'kr', 'au', 'ng', 'za'];

    public function scrape(): int
    {
        $scraperUrl = config('services.google_play.scraper_url', 'http://localhost:3001');
        $count = 0;

        foreach (self::COUNTRIES as $country) {
            foreach (['APPLICATION', 'GAME'] as $category) {
                try {
                    $response = Http::timeout(30)->get("{$scraperUrl}/api/apps", [
                        'collection' => 'topselling_new_free',
                        'category' => $category,
                        'country' => $country,
                        'num' => 50,
                    ]);

                    if (!$response->ok()) continue;

                    foreach ($response->json() as $app) {
                        $count += $this->upsertApp($app);
                    }
                } catch (\Exception $e) {
                    Log::warning("Google Play [{$country}/{$category}]: " . $e->getMessage());
                }
            }
        }

        Log::info("Google Play: {$count} nouvelles apps ajoutées");
        return $count;
    }

    private function upsertApp(array $app): int
    {
        $name = $app['title'] ?? '';
        if (empty($name)) return 0;

        $appId = $app['appId'] ?? '';
        $sourceUrl = "https://play.google.com/store/apps/details?id={$appId}";

        $existing = DiscoveredApp::where('source', 'google_play')
            ->where('source_url', $sourceUrl)
            ->first();

        if ($existing) return 0;

        // Google Play ne donne pas toujours la date exacte de sortie
        $releaseDate = now()->toDateString();

        DiscoveredApp::create([
            'name' => $name,
            'summary_fr' => $app['summary'] ?? null,
            'category' => $this->mapCategory($app['genre'] ?? ''),
            'platform' => 'android',
            'release_date' => $releaseDate,
            'source' => 'google_play',
            'source_url' => $sourceUrl,
            'icon_url' => $app['icon'] ?? null,
        ]);

        return 1;
    }

    private function mapCategory(string $genre): string
    {
        $mapping = [
            'Productivity' => 'Productivité',
            'Social' => 'Social',
            'Health & Fitness' => 'Santé',
            'Finance' => 'Finance',
            'Education' => 'Éducation',
            'Entertainment' => 'Divertissement',
            'Tools' => 'Utilitaires',
            'Communication' => 'Communication',
            'Shopping' => 'Shopping',
            'Travel & Local' => 'Voyage',
            'Food & Drink' => 'Alimentation',
            'Music & Audio' => 'Musique',
            'Photography' => 'Photo & Vidéo',
            'Video Players & Editors' => 'Photo & Vidéo',
            'Business' => 'Business',
            'Lifestyle' => 'Lifestyle',
            'News & Magazines' => 'Actualités',
            'Sports' => 'Sport',
            'Dating' => 'Rencontres',
            'Medical' => 'Médical',
        ];

        return $mapping[$genre] ?? 'Autre';
    }
}
