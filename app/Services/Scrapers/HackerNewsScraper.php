<?php

namespace App\Services\Scrapers;

use App\Models\DiscoveredApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HackerNewsScraper
{
    /**
     * Scrape les "Show HN" et "Launch HN" récents via l'API Algolia HN.
     */
    public function scrape(): int
    {
        $count = 0;

        foreach (['show_hn', 'launch_hn'] as $tag) {
            try {
                $response = Http::timeout(15)->get('https://hn.algolia.com/api/v1/search', [
                    'tags' => $tag,
                    'hitsPerPage' => 50,
                    'numericFilters' => 'created_at_i>' . now()->subDays(7)->timestamp,
                ]);

                if (!$response->ok()) continue;

                foreach ($response->json()['hits'] ?? [] as $hit) {
                    $count += $this->upsertApp($hit);
                }
            } catch (\Exception $e) {
                Log::warning("HackerNews [{$tag}]: " . $e->getMessage());
            }
        }

        Log::info("HackerNews: {$count} nouvelles apps ajoutées");
        return $count;
    }

    private function upsertApp(array $hit): int
    {
        $title = $hit['title'] ?? '';
        // Nettoyer "Show HN: " ou "Launch HN: "
        $name = preg_replace('/^(Show|Launch)\s+HN:\s*/i', '', $title);
        if (empty($name)) return 0;

        $sourceUrl = $hit['url'] ?? "https://news.ycombinator.com/item?id={$hit['objectID']}";

        $existing = DiscoveredApp::where('source', 'hackernews')
            ->where('source_url', $sourceUrl)
            ->first();

        if ($existing) return 0;

        DiscoveredApp::create([
            'name' => $name,
            'summary_fr' => null,
            'category' => 'Autre',
            'platform' => 'web',
            'release_date' => isset($hit['created_at'])
                ? \Carbon\Carbon::parse($hit['created_at'])->toDateString()
                : now()->toDateString(),
            'source' => 'hackernews',
            'source_url' => $sourceUrl,
        ]);

        return 1;
    }
}
