<?php

namespace App\Services\Scrapers;

use App\Models\DiscoveredApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductHuntScraper
{
    /**
     * Scrape les posts récents de Product Hunt via leur API GraphQL.
     * Nécessite un token Developer (gratuit) dans PRODUCTHUNT_TOKEN.
     */
    public function scrape(): int
    {
        $token = config('services.producthunt.token');
        if (!$token) {
            Log::warning('ProductHunt: PRODUCTHUNT_TOKEN non configuré');
            return 0;
        }

        $count = 0;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json',
            ])->post('https://api.producthunt.com/v2/api/graphql', [
                'query' => $this->getQuery(),
            ]);

            if (!$response->ok()) {
                Log::error('ProductHunt API error: ' . $response->status());
                return 0;
            }

            $posts = data_get($response->json(), 'data.posts.edges', []);

            foreach ($posts as $edge) {
                $node = $edge['node'];
                $count += $this->upsertApp($node);
            }

            Log::info("ProductHunt: {$count} nouvelles apps ajoutées");
        } catch (\Exception $e) {
            Log::error('ProductHunt scraper error: ' . $e->getMessage());
        }

        return $count;
    }

    private function upsertApp(array $node): int
    {
        $name = $node['name'] ?? '';
        if (empty($name)) return 0;

        $releaseDate = isset($node['createdAt'])
            ? \Carbon\Carbon::parse($node['createdAt'])->toDateString()
            : now()->toDateString();

        // Filtrer < 2026
        if ($releaseDate < '2025-01-01') return 0;

        $existing = DiscoveredApp::where('source', 'producthunt')
            ->where('source_url', $node['url'] ?? '')
            ->first();

        if ($existing) return 0;

        // Détecter la plateforme depuis les topics
        $topics = collect($node['topics']['edges'] ?? [])->pluck('node.name')->implode(', ');
        $platform = $this->detectPlatform($topics, $node['tagline'] ?? '');

        DiscoveredApp::create([
            'name' => $name,
            'summary_fr' => $node['tagline'] ?? null, // Sera traduit par Claude
            'category' => $this->extractCategory($topics),
            'platform' => $platform,
            'release_date' => $releaseDate,
            'source' => 'producthunt',
            'source_url' => $node['url'] ?? null,
            'icon_url' => $node['thumbnail']['url'] ?? null,
        ]);

        return 1;
    }

    private function detectPlatform(string $topics, string $tagline): string
    {
        $text = strtolower($topics . ' ' . $tagline);
        if (str_contains($text, 'ios') || str_contains($text, 'iphone')) return 'ios';
        if (str_contains($text, 'android')) return 'android';
        if (str_contains($text, 'pwa') || str_contains($text, 'progressive')) return 'pwa';
        return 'web';
    }

    private function extractCategory(string $topics): string
    {
        $mapping = [
            'productivity' => 'Productivité',
            'social' => 'Social',
            'health' => 'Santé',
            'fintech' => 'Finance',
            'developer' => 'Développement',
            'design' => 'Design',
            'marketing' => 'Marketing',
            'education' => 'Éducation',
            'ai' => 'Intelligence Artificielle',
            'artificial intelligence' => 'Intelligence Artificielle',
            'gaming' => 'Jeux',
            'music' => 'Musique',
            'photo' => 'Photo & Vidéo',
            'video' => 'Photo & Vidéo',
            'travel' => 'Voyage',
            'food' => 'Alimentation',
            'fitness' => 'Fitness',
            'crypto' => 'Crypto',
        ];

        $topicsLower = strtolower($topics);
        foreach ($mapping as $keyword => $category) {
            if (str_contains($topicsLower, $keyword)) {
                return $category;
            }
        }

        return 'Autre';
    }

    private function getQuery(): string
    {
        return <<<'GRAPHQL'
        {
            posts(order: NEWEST, first: 50) {
                edges {
                    node {
                        name
                        tagline
                        url
                        createdAt
                        thumbnail {
                            url
                        }
                        topics(first: 5) {
                            edges {
                                node {
                                    name
                                }
                            }
                        }
                    }
                }
            }
        }
        GRAPHQL;
    }
}
