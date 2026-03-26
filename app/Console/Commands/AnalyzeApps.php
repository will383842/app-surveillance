<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeAppsJob;
use App\Models\DiscoveredApp;
use Illuminate\Console\Command;

class AnalyzeApps extends Command
{
    protected $signature = 'apps:analyze {--force : Ré-analyser toutes les apps}';
    protected $description = 'Analyser les apps non encore analysées via Claude API';

    public function handle(): int
    {
        if ($this->option('force')) {
            DiscoveredApp::query()->update(['analyzed' => false, 'analyzed_at' => null]);
            $this->info('Toutes les apps marquées pour ré-analyse.');
        }

        $pending = DiscoveredApp::where('analyzed', false)->count();
        $this->info("{$pending} apps à analyser.");

        if ($pending > 0) {
            AnalyzeAppsJob::dispatch();
            $this->info('Job dispatché en queue.');
        }

        return 0;
    }
}
