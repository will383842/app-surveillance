<?php

use Illuminate\Support\Facades\Schedule;

// Scrape toutes les 6 heures
Schedule::command('apps:scrape')->everySixHours();

// Analyser les apps non analysées toutes les heures
Schedule::command('apps:analyze')->hourly();
