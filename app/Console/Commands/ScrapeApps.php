<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeAppsJob;
use Illuminate\Console\Command;

class ScrapeApps extends Command
{
    protected $signature = 'apps:scrape';
    protected $description = 'Scrape les nouvelles apps depuis toutes les sources';

    public function handle(): int
    {
        $this->info('Lancement du scraping...');
        ScrapeAppsJob::dispatch();
        $this->info('Job dispatché en queue.');
        return 0;
    }
}
