@extends('layouts.app')

@section('title', 'Suivi nouvelles APPs')

@section('nav-stats')
    <span>{{ $totalApps }} apps</span>
    <span class="text-green-400">{{ $analyzedApps }} analysees</span>
    <span class="text-yellow-400">{{ $pendingApps }} en attente</span>
    <span>Moy. explosion: {{ number_format($avgExplosion ?? 0, 1) }}/10</span>
    <a href="{{ route('export.csv', request()->query()) }}" class="bg-dark-700 hover:bg-dark-800 px-3 py-1 rounded text-white">
        Export CSV
    </a>
@endsection

@section('content')
    {{-- Filtres --}}
    <form method="GET" action="/" class="bg-dark-800 rounded-lg p-4 mb-6" x-data="{ open: true }">
        <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open">
            <h2 class="text-lg font-semibold text-white">Filtres</h2>
            <span x-text="open ? '−' : '+'" class="text-xl text-gray-400"></span>
        </div>

        <div x-show="open" x-cloak class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div class="col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Rechercher une app..."
                       class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <select name="min_score" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Score min.</option>
                    @for ($i = 5; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ request('min_score') == $i ? 'selected' : '' }}>{{ $i }}+/10</option>
                    @endfor
                </select>
            </div>

            <div>
                <select name="category" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Categorie</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="platform" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Plateforme</option>
                    @foreach ($platforms as $plat)
                        <option value="{{ $plat }}" {{ request('platform') == $plat ? 'selected' : '' }}>{{ strtoupper($plat) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="age_group" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Public</option>
                    <option value="gen_z" {{ request('age_group') == 'gen_z' ? 'selected' : '' }}>Gen Z</option>
                    <option value="millennials" {{ request('age_group') == 'millennials' ? 'selected' : '' }}>Millennials</option>
                    <option value="adultes" {{ request('age_group') == 'adultes' ? 'selected' : '' }}>Adultes</option>
                    <option value="seniors" {{ request('age_group') == 'seniors' ? 'selected' : '' }}>Seniors</option>
                    <option value="tous" {{ request('age_group') == 'tous' ? 'selected' : '' }}>Tous</option>
                </select>
            </div>

            <div>
                <select name="business_model" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Modele eco.</option>
                    @foreach (['gratuit' => 'Gratuit', 'freemium' => 'Freemium', 'abonnement' => 'Abonnement', 'pub' => 'Pub', 'payant' => 'Payant'] as $val => $label)
                        <option value="{{ $val }}" {{ request('business_model') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="market_size" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Marche</option>
                    @foreach (['niche' => 'Niche', 'moyen' => 'Moyen', 'enorme' => 'Enorme'] as $val => $label)
                        <option value="{{ $val }}" {{ request('market_size') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="competition_level" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Concurrence</option>
                    @foreach (['faible' => 'Faible', 'moyenne' => 'Moyenne', 'saturee' => 'Saturee'] as $val => $label)
                        <option value="{{ $val }}" {{ request('competition_level') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm text-white font-medium">
                    Filtrer
                </button>
                <a href="/" class="bg-dark-700 hover:bg-gray-600 px-4 py-2 rounded text-sm text-gray-300">
                    Reset
                </a>
            </div>
        </div>
    </form>

    {{-- Cards --}}
    <div class="space-y-4">
        @forelse ($apps as $app)
            <div class="bg-dark-800 rounded-lg p-5 hover:bg-dark-700 transition-colors cursor-pointer"
                 onclick="window.location='{{ route('app.show', $app) }}'">

                {{-- Ligne 1 : Scores + Nom + Categorie --}}
                <div class="flex items-center gap-4 mb-3">
                    {{-- Scores --}}
                    <div class="flex gap-2 shrink-0">
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $app->explosion_score >= 7 ? 'bg-green-900 text-green-300' : ($app->explosion_score >= 4 ? 'bg-yellow-900 text-yellow-300' : 'bg-red-900 text-red-300') }}">
                            Explosion {{ $app->explosion_score }}/10
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $app->buzz_score >= 7 ? 'bg-green-900 text-green-300' : ($app->buzz_score >= 4 ? 'bg-yellow-900 text-yellow-300' : 'bg-red-900 text-red-300') }}">
                            Buzz {{ $app->buzz_score }}/10
                        </span>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $app->k_factor >= 7 ? 'bg-green-900 text-green-300' : ($app->k_factor >= 4 ? 'bg-yellow-900 text-yellow-300' : 'bg-red-900 text-red-300') }}">
                            Viralite {{ $app->k_factor }}/10
                        </span>
                    </div>

                    {{-- Nom + icone --}}
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        @if ($app->icon_url)
                            <img src="{{ $app->icon_url }}" alt="" class="w-10 h-10 rounded-lg shrink-0">
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-white font-semibold text-lg truncate">{{ $app->name }}</h3>
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <span class="bg-blue-900/50 text-blue-300 px-2 py-0.5 rounded">{{ $app->category }}</span>
                                <span class="uppercase">{{ $app->platform }}</span>
                                <span>{{ $app->release_date?->format('d/m/Y') }}</span>
                                @if ($app->age_group)
                                    <span class="bg-purple-900/50 text-purple-300 px-2 py-0.5 rounded">{{ ucfirst(str_replace('_', ' ', $app->age_group)) }}</span>
                                @endif
                                @if ($app->business_model)
                                    <span class="bg-emerald-900/50 text-emerald-300 px-2 py-0.5 rounded">{{ ucfirst($app->business_model) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ligne 2 : Ce que fait l'app --}}
                <p class="text-gray-300 text-sm mb-3">{{ $app->summary_fr }}</p>

                {{-- Ligne 3 : Cible + Ce qui est exceptionnel --}}
                <div class="grid md:grid-cols-2 gap-3 mb-3">
                    @if ($app->target_audience_fr)
                        <div class="text-xs">
                            <span class="text-blue-400 font-semibold">Pour qui :</span>
                            <span class="text-gray-400">{{ $app->target_audience_fr }}</span>
                        </div>
                    @endif
                    @if ($app->exceptional_factor_fr)
                        <div class="text-xs">
                            <span class="text-yellow-400 font-semibold">Ce qui est unique :</span>
                            <span class="text-gray-400">{{ $app->exceptional_factor_fr }}</span>
                        </div>
                    @endif
                </div>

                {{-- Ligne 4 : Points + et - --}}
                <div class="grid md:grid-cols-2 gap-3 mb-3">
                    @if (is_array($app->pros_fr) && count($app->pros_fr))
                        <div class="text-xs">
                            <span class="text-green-400 font-semibold">Points forts :</span>
                            <span class="text-gray-400">{{ implode(' / ', $app->pros_fr) }}</span>
                        </div>
                    @endif
                    @if (is_array($app->cons_fr) && count($app->cons_fr))
                        <div class="text-xs">
                            <span class="text-red-400 font-semibold">Points faibles :</span>
                            <span class="text-gray-400">{{ implode(' / ', $app->cons_fr) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Ligne 5 : Badges infos --}}
                <div class="flex flex-wrap gap-2 text-xs">
                    @if ($app->feature_count)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">{{ $app->feature_count }} fonctions</span>
                    @endif
                    @if ($app->retention_estimate)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">Retention {{ $app->retention_estimate }}</span>
                    @endif
                    @if ($app->usage_duration)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">Usage {{ $app->usage_duration }}</span>
                    @endif
                    @if ($app->group_belonging)
                        <span class="bg-dark-900 text-blue-400 px-2 py-1 rounded">Communaute</span>
                    @endif
                    @if ($app->user_recognition)
                        <span class="bg-dark-900 text-purple-400 px-2 py-1 rounded">Mise en avant</span>
                    @endif
                    @if ($app->competition_level)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">Concurrence {{ $app->competition_level }}</span>
                    @endif
                    @if ($app->market_size)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">Marche {{ $app->market_size }}</span>
                    @endif
                    @if ($app->technical_effort)
                        <span class="bg-dark-900 text-gray-400 px-2 py-1 rounded">Difficulte {{ $app->technical_effort }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-dark-800 rounded-lg p-8 text-center text-gray-500">
                Aucune app trouvee. Le scraping tourne automatiquement toutes les 6h.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $apps->links() }}
    </div>
@endsection
