<?php

namespace App\Jobs;

use App\Models\DiscoveredApp;
use App\Services\ClaudeAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeAppsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min max
    public int $tries = 1;

    /**
     * Analyse par batch de 20 apps non analysées.
     * Espace les appels Claude de 2 secondes pour éviter le rate limit.
     */
    public function handle(): void
    {
        $analyzer = new ClaudeAnalyzer();
        $batchSize = 20;

        $apps = DiscoveredApp::where('analyzed', false)
            ->orderBy('created_at', 'desc')
            ->limit($batchSize)
            ->get();

        if ($apps->isEmpty()) {
            Log::info('AnalyzeApps: aucune app à analyser');
            return;
        }

        $success = 0;
        $failed = 0;

        foreach ($apps as $app) {
            $result = $analyzer->analyze($app);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }

            // Rate limiting — 2 secondes entre chaque appel
            sleep(2);
        }

        Log::info("AnalyzeApps: {$success} analysées, {$failed} échouées");

        // S'il reste des apps non analysées, relancer le job
        $remaining = DiscoveredApp::where('analyzed', false)->count();
        if ($remaining > 0) {
            self::dispatch()->delay(now()->addMinutes(2));
            Log::info("AnalyzeApps: {$remaining} apps restantes, relance dans 2 min");
        }
    }
}
