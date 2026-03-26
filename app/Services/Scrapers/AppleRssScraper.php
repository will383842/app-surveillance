<?php

namespace App\Services\Scrapers;

use App\Models\DiscoveredApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppleRssScraper
{
    /**
     * Storefronts Apple RSS — top pays par continent.
     * Feed: https://rss.applemarketingtools.com/api/v2/{country}/apps/most-recent/50/apps.json
     */
    private const STOREFRONTS = [
        // Europe
        'fr', 'de', 'gb', 'es', 'it', 'nl', 'se', 'pl',
        // Amérique
        'us', 'ca', 'br', 'mx', 'ar', 'co',
        // Asie
        'jp', 'kr', 'cn', 'in', 'th', 'id', 'sg',
        // Afrique & Moyen-Orient
        'za', 'ng', 'ae', 'sa', 'eg',
        // Océanie
        'au', 'nz',
    ];

    public function scrape(): int
    {
        $count = 0;

        foreach (self::STOREFRONTS as $country) {
            try {
                $count += $this->scrapeCountry($country);
            } catch (\Exception $e) {
                Log::warning("Apple RSS [{$country}]: " . $e->getMessage());
            }
        }

        Log::info("Apple RSS: {$count} nouvelles apps ajoutées");
        return $count;
    }

    private function scrapeCountry(string $country): int
    {
        $url = "https://rss.marketingtools.apple.com/api/v2/{$country}/apps/most-recent/50/apps.json";

        $response = Http::timeout(15)->get($url);

        if (!$response->ok()) {
            return 0;
        }

        $results = data_get($response->json(), 'feed.results', []);
        $count = 0;

        foreach ($results as $app) {
            $count += $this->upsertApp($app, $country);
        }

        return $count;
    }

    private function upsertApp(array $app, string $country): int
    {
        $name = $app['name'] ?? '';
        if (empty($name)) return 0;

        $releaseDate = $app['releaseDate'] ?? now()->toDateString();
        if ($releaseDate < '2026-01-01') return 0;

        // Déduplique par URL du store
        $sourceUrl = $app['url'] ?? '';
        $existing = DiscoveredApp::where('source', 'apple_rss')
            ->where('source_url', $sourceUrl)
            ->first();

        if ($existing) return 0;

        $genres = collect($app['genres'] ?? [])->pluck('name')->implode(', ');

        DiscoveredApp::create([
            'name' => $name,
            'summary_fr' => null, // Sera analysé par Claude
            'category' => $this->mapGenre($genres),
            'platform' => 'ios',
            'release_date' => $releaseDate,
            'source' => 'apple_rss',
            'source_url' => $sourceUrl,
            'icon_url' => $app['artworkUrl100'] ?? null,
        ]);

        return 1;
    }

    private function mapGenre(string $genres): string
    {
        $genresLower = strtolower($genres);
        $mapping = [
            'productivity' => 'Productivité',
            'social networking' => 'Social',
            'health' => 'Santé',
            'finance' => 'Finance',
            'developer' => 'Développement',
            'education' => 'Éducation',
            'games' => 'Jeux',
            'music' => 'Musique',
            'photo' => 'Photo & Vidéo',
            'video' => 'Photo & Vidéo',
            'travel' => 'Voyage',
            'food' => 'Alimentation',
            'entertainment' => 'Divertissement',
            'business' => 'Business',
            'utilities' => 'Utilitaires',
            'lifestyle' => 'Lifestyle',
            'shopping' => 'Shopping',
            'sports' => 'Sport',
            'news' => 'Actualités',
            'weather' => 'Météo',
            'navigation' => 'Navigation',
            'reference' => 'Référence',
            'medical' => 'Médical',
        ];

        foreach ($mapping as $keyword => $category) {
            if (str_contains($genresLower, $keyword)) {
                return $category;
            }
        }

        return 'Autre';
    }
}
