<?php

namespace App\Jobs;

use App\Services\Scrapers\AppleRssScraper;
use App\Services\Scrapers\GooglePlayScraper;
use App\Services\Scrapers\HackerNewsScraper;
use App\Services\Scrapers\ProductHuntScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeAppsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 2;

    public function handle(): void
    {
        $total = 0;

        $scrapers = [
            'ProductHunt' => new ProductHuntScraper(),
            'Apple RSS' => new AppleRssScraper(),
            'Google Play' => new GooglePlayScraper(),
            'HackerNews' => new HackerNewsScraper(),
        ];

        foreach ($scrapers as $name => $scraper) {
            try {
                $count = $scraper->scrape();
                $total += $count;
                Log::info("Scraper [{$name}]: {$count} apps");
            } catch (\Exception $e) {
                Log::error("Scraper [{$name}] failed: " . $e->getMessage());
            }
        }

        Log::info("Scraping terminé: {$total} nouvelles apps au total");

        // Lancer l'analyse des apps non analysées
        AnalyzeAppsJob::dispatch();
    }
}
