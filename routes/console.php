<?php

use Illuminate\Support\Facades\Schedule;

// Scrape toutes les 3 heures (8 fois par jour)
Schedule::command('apps:scrape')->everyThreeHours();

// Analyser les apps non analysées toutes les 30 min
Schedule::command('apps:analyze')->everyThirtyMinutes();
