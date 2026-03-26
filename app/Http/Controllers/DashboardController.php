<?php

namespace App\Http\Controllers;

use App\Models\DiscoveredApp;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = DiscoveredApp::analyzed();

        // Filtre dossier
        if ($request->filled('folder')) {
            if ($request->folder === 'non_classe') {
                $query->whereNull('folder');
            } else {
                $query->where('folder', $request->folder);
            }
        }

        // Filtres
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('business_model')) {
            $query->where('business_model', $request->business_model);
        }
        if ($request->filled('market_size')) {
            $query->where('market_size', $request->market_size);
        }
        if ($request->filled('competition_level')) {
            $query->where('competition_level', $request->competition_level);
        }
        if ($request->filled('min_score')) {
            $query->where('explosion_score', '>=', intval($request->min_score));
        }
        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('summary_fr', 'ilike', "%{$search}%")
                  ->orWhere('target_audience_fr', 'ilike', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort', 'explosion_score');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = [
            'explosion_score', 'buzz_score', 'k_factor', 'feature_count',
            'release_date', 'name', 'created_at',
        ];
        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'explosion_score';
        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        $apps = $query->paginate(30)->withQueryString();

        // Stats
        $totalApps = DiscoveredApp::analyzed()->count();
        $analyzedApps = $totalApps;
        $pendingApps = DiscoveredApp::where('analyzed', false)->count();
        $avgExplosion = DiscoveredApp::analyzed()->avg('explosion_score');

        // Compteurs dossiers
        $folderCounts = [
            'tous' => $totalApps,
            'top' => DiscoveredApp::analyzed()->where('folder', 'top')->count(),
            'a_voir' => DiscoveredApp::analyzed()->where('folder', 'a_voir')->count(),
            'archive' => DiscoveredApp::analyzed()->where('folder', 'archive')->count(),
            'non_classe' => DiscoveredApp::analyzed()->whereNull('folder')->count(),
        ];

        // Options pour les filtres
        $categories = DiscoveredApp::analyzed()->distinct()->pluck('category')->filter()->sort()->values();
        $platforms = DiscoveredApp::analyzed()->distinct()->pluck('platform')->filter()->sort()->values();
        $sources = DiscoveredApp::analyzed()->distinct()->pluck('source')->filter()->sort()->values();

        return view('dashboard', compact(
            'apps', 'totalApps', 'analyzedApps', 'pendingApps', 'avgExplosion',
            'categories', 'platforms', 'sources',
            'sortBy', 'sortDir', 'folderCounts'
        ));
    }

    public function show(DiscoveredApp $app)
    {
        return view('app-detail', compact('app'));
    }

    public function setFolder(Request $request, DiscoveredApp $app)
    {
        $folder = $request->input('folder');
        $allowed = ['top', 'a_voir', 'archive', null];

        if (!in_array($folder, $allowed, true)) {
            $folder = null;
        }

        $app->update(['folder' => $folder]);

        // Si requete AJAX, retourner JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'folder' => $folder]);
        }

        return back();
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = DiscoveredApp::analyzed()->orderByDesc('explosion_score');

        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('platform')) $query->where('platform', $request->platform);
        if ($request->filled('min_score')) $query->where('explosion_score', '>=', intval($request->min_score));
        if ($request->filled('folder')) {
            if ($request->folder === 'non_classe') {
                $query->whereNull('folder');
            } else {
                $query->where('folder', $request->folder);
            }
        }

        $apps = $query->get();

        return response()->streamDownload(function () use ($apps) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Dossier', 'Nom', 'Resume', 'Categorie', 'Plateforme', 'Date sortie',
                'Nb Fonctions', 'Exceptionnel', 'Cible', 'Modele eco',
                'Points +', 'Points -', 'Score explosion /10', 'Verdict explosion',
                'Score buzz /10', 'Retention', 'K-factor /10', 'Partage',
                'Communaute', 'Duree usage', 'Mise en avant', 'Concurrence',
                'Difficulte technique', 'Taille marche', 'URL',
            ], ';');

            foreach ($apps as $app) {
                $folderLabels = ['top' => 'Top a etudier', 'a_voir' => 'A voir', 'archive' => 'Archive'];
                fputcsv($handle, [
                    $folderLabels[$app->folder] ?? 'Non classe',
                    $app->name,
                    $app->summary_fr,
                    $app->category,
                    $app->platform,
                    $app->release_date?->format('Y-m-d'),
                    $app->feature_count,
                    $app->exceptional_factor_fr,
                    $app->target_audience_fr,
                    $app->business_model,
                    is_array($app->pros_fr) ? implode(' | ', $app->pros_fr) : $app->pros_fr,
                    is_array($app->cons_fr) ? implode(' | ', $app->cons_fr) : $app->cons_fr,
                    $app->explosion_score,
                    $app->explosion_verdict_fr,
                    $app->buzz_score,
                    $app->retention_estimate,
                    $app->k_factor,
                    $app->sharing_mechanisms_fr,
                    $app->group_belonging ? 'Oui' : 'Non',
                    $app->usage_duration,
                    $app->user_recognition ? 'Oui' : 'Non',
                    $app->competition_level,
                    $app->technical_effort,
                    $app->market_size,
                    $app->source_url,
                ], ';');
            }

            fclose($handle);
        }, 'apps-surveillance-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
