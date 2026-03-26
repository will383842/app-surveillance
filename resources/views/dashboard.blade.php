@extends('layouts.app')

@section('title', 'Suivi nouvelles APPs')

@section('nav-stats')
    <div class="flex items-center gap-3">
        <span class="glass px-3 py-1 rounded-full text-xs">{{ $totalApps }} apps</span>
        <span class="glass px-3 py-1 rounded-full text-xs text-green-400">{{ $analyzedApps }} analysees</span>
        @if ($pendingApps > 0)
            <span class="glass px-3 py-1 rounded-full text-xs text-amber-400">{{ $pendingApps }} en attente</span>
        @endif
        <a href="{{ route('export.csv', request()->query()) }}" class="glass px-3 py-1 rounded-full text-xs hover:text-white transition-colors">
            Exporter CSV
        </a>
    </div>
@endsection

@section('content')
    {{-- Onglets dossiers --}}
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        @php
            $currentFolder = request('folder');
            $tabs = [
                [null, 'Toutes', $folderCounts['tous'], 'from-gray-600 to-gray-700'],
                ['top', 'Top a etudier', $folderCounts['top'], 'from-emerald-600 to-emerald-700'],
                ['a_voir', 'A voir', $folderCounts['a_voir'], 'from-amber-600 to-amber-700'],
                ['archive', 'Archivees', $folderCounts['archive'], 'from-gray-500 to-gray-600'],
                ['non_classe', 'Non classees', $folderCounts['non_classe'], 'from-indigo-600 to-indigo-700'],
            ];
        @endphp
        @foreach ($tabs as [$folder, $label, $count, $gradient])
            @php
                $isActive = $currentFolder === $folder;
                $href = $folder ? '/?folder=' . $folder : '/';
            @endphp
            <a href="{{ $href }}"
               class="shrink-0 flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ $isActive ? 'bg-gradient-to-r ' . $gradient . ' text-white shadow-lg' : 'glass text-gray-400 hover:text-white' }}">
                {{ $label }}
                <span class="px-2 py-0.5 rounded-full text-xs {{ $isActive ? 'bg-white/20' : 'bg-white/5' }}">{{ $count }}</span>
            </a>
        @endforeach
    </div>

    {{-- Filtres --}}
    <div class="glass rounded-2xl p-5 mb-8" x-data="{ open: true }">
        <div class="flex items-center justify-between mb-4 cursor-pointer" @click="open = !open">
            <h2 class="text-base font-semibold text-white">Filtres</h2>
            <button class="text-gray-400 hover:text-white transition-colors">
                <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                <svg x-show="!open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <form method="GET" action="/" x-show="open" x-cloak>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <div class="col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Rechercher une app..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>

                @php
                    $selects = [
                        ['name' => 'min_score', 'placeholder' => 'Score min.', 'options' => [5 => '5+/10', 6 => '6+/10', 7 => '7+/10', 8 => '8+/10', 9 => '9+/10']],
                        ['name' => 'category', 'placeholder' => 'Categorie', 'options' => $categories->mapWithKeys(fn($c) => [$c => $c])->toArray()],
                        ['name' => 'platform', 'placeholder' => 'Plateforme', 'options' => $platforms->mapWithKeys(fn($p) => [$p => strtoupper($p)])->toArray()],
                        ['name' => 'age_group', 'placeholder' => 'Public', 'options' => ['gen_z' => 'Gen Z', 'millennials' => 'Millennials', 'adultes' => 'Adultes', 'seniors' => 'Seniors', 'tous' => 'Tous']],
                        ['name' => 'business_model', 'placeholder' => 'Modele eco.', 'options' => ['gratuit' => 'Gratuit', 'freemium' => 'Freemium', 'abonnement' => 'Abonnement', 'pub' => 'Pub', 'payant' => 'Payant']],
                        ['name' => 'market_size', 'placeholder' => 'Marche', 'options' => ['niche' => 'Niche', 'moyen' => 'Moyen', 'enorme' => 'Enorme']],
                        ['name' => 'competition_level', 'placeholder' => 'Concurrence', 'options' => ['faible' => 'Faible', 'moyenne' => 'Moyenne', 'saturee' => 'Saturee']],
                    ];
                @endphp
                @foreach ($selects as $sel)
                    <div>
                        <select name="{{ $sel['name'] }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:outline-none transition-all appearance-none">
                            <option value="">{{ $sel['placeholder'] }}</option>
                            @foreach ($sel['options'] as $val => $label)
                                <option value="{{ $val }}" {{ request($sel['name']) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 rounded-xl text-sm text-white font-medium transition-colors">
                        Filtrer
                    </button>
                    <a href="/" class="bg-white/5 hover:bg-white/10 px-4 py-2.5 rounded-xl text-sm text-gray-400 hover:text-white transition-all">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Score moyen --}}
    @if ($avgExplosion)
        <div class="text-center mb-6">
            <span class="text-sm text-gray-500">Score moyen d'explosion :</span>
            <span class="gradient-text text-lg font-bold ml-1">{{ number_format($avgExplosion, 1) }}/10</span>
        </div>
    @endif

    {{-- Liste des apps --}}
    <div class="space-y-4">
        @forelse ($apps as $app)
            <a href="{{ $app->folder === 'archive' ? '#' : route('app.show', $app) }}" class="block glass-strong rounded-2xl p-5 card-hover {{ $app->folder === 'archive' ? 'opacity-50' : '' }}">
                {{-- Header : Scores + Nom --}}
                <div class="flex items-start gap-4 mb-4">
                    @if ($app->icon_url)
                        <img src="{{ $app->icon_url }}" alt="" class="w-12 h-12 rounded-xl shadow-lg shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ mb_substr($app->name, 0, 1) }}
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="text-white font-semibold text-lg truncate">{{ $app->name }}</h3>
                            <div class="flex gap-1.5 shrink-0">
                                <span class="score-pill px-2 py-0.5 rounded-lg text-xs font-bold {{ $app->explosion_score >= 7 ? 'bg-emerald-500/20 text-emerald-400' : ($app->explosion_score >= 4 ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                                    {{ $app->explosion_score }}/10
                                </span>
                                <span class="score-pill px-2 py-0.5 rounded-lg text-xs font-bold {{ $app->buzz_score >= 7 ? 'bg-emerald-500/20 text-emerald-400' : ($app->buzz_score >= 4 ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                                    Buzz {{ $app->buzz_score }}
                                </span>
                                <span class="score-pill px-2 py-0.5 rounded-lg text-xs font-bold {{ $app->k_factor >= 7 ? 'bg-emerald-500/20 text-emerald-400' : ($app->k_factor >= 4 ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400') }}">
                                    Viral {{ $app->k_factor }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="bg-indigo-500/20 text-indigo-300 px-2.5 py-0.5 rounded-lg">{{ $app->category }}</span>
                            <span class="bg-white/5 text-gray-400 px-2.5 py-0.5 rounded-lg uppercase">{{ $app->platform }}</span>
                            @if ($app->age_group)
                                <span class="bg-purple-500/20 text-purple-300 px-2.5 py-0.5 rounded-lg">{{ ucfirst(str_replace('_', ' ', $app->age_group)) }}</span>
                            @endif
                            @if ($app->business_model)
                                <span class="bg-emerald-500/20 text-emerald-300 px-2.5 py-0.5 rounded-lg">{{ ucfirst($app->business_model) }}</span>
                            @endif
                            <span class="text-gray-500">{{ $app->release_date?->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <p class="text-gray-300 text-sm leading-relaxed mb-3">{{ $app->summary_fr }}</p>

                {{-- Cible + Unique --}}
                <div class="grid md:grid-cols-2 gap-3 mb-3">
                    @if ($app->target_audience_fr)
                        <div class="text-xs">
                            <span class="text-indigo-400 font-medium">Pour qui :</span>
                            <span class="text-gray-400 ml-1">{{ $app->target_audience_fr }}</span>
                        </div>
                    @endif
                    @if ($app->exceptional_factor_fr)
                        <div class="text-xs">
                            <span class="text-amber-400 font-medium">Unique :</span>
                            <span class="text-gray-400 ml-1">{{ $app->exceptional_factor_fr }}</span>
                        </div>
                    @endif
                </div>

                {{-- Points forts/faibles --}}
                <div class="grid md:grid-cols-2 gap-3 mb-3">
                    @if (is_array($app->pros_fr) && count($app->pros_fr))
                        <div class="text-xs">
                            <span class="text-emerald-400 font-medium">Points forts :</span>
                            <span class="text-gray-400 ml-1">{{ implode(' · ', $app->pros_fr) }}</span>
                        </div>
                    @endif
                    @if (is_array($app->cons_fr) && count($app->cons_fr))
                        <div class="text-xs">
                            <span class="text-red-400 font-medium">Points faibles :</span>
                            <span class="text-gray-400 ml-1">{{ implode(' · ', $app->cons_fr) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Boutons de classement + Badges --}}
                <div class="flex items-center justify-between mt-1">
                    {{-- Boutons dossier --}}
                    <div class="flex gap-1.5" x-data="{ current: '{{ $app->folder }}' }" @click.stop>
                        <button @click="setFolder({{ $app->id }}, 'top', $el)"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                :class="current === 'top' ? 'bg-emerald-500 text-white' : 'bg-white/5 text-gray-400 hover:bg-emerald-500/20 hover:text-emerald-400'">
                            Top a etudier
                        </button>
                        <button @click="setFolder({{ $app->id }}, 'a_voir', $el)"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                :class="current === 'a_voir' ? 'bg-amber-500 text-white' : 'bg-white/5 text-gray-400 hover:bg-amber-500/20 hover:text-amber-400'">
                            A voir
                        </button>
                        <button @click="setFolder({{ $app->id }}, 'archive', $el)"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                :class="current === 'archive' ? 'bg-gray-500 text-white' : 'bg-white/5 text-gray-400 hover:bg-gray-500/20 hover:text-gray-300'">
                            Archiver
                        </button>
                    </div>

                    {{-- Badges infos --}}
                    <div class="flex flex-wrap gap-1.5 text-xs">
                        @if ($app->feature_count)
                            <span class="bg-white/5 text-gray-400 px-2 py-1 rounded-lg">{{ $app->feature_count }} fonctions</span>
                        @endif
                        @if ($app->retention_estimate)
                            <span class="bg-white/5 text-gray-400 px-2 py-1 rounded-lg">Retention {{ $app->retention_estimate }}</span>
                        @endif
                        @if ($app->group_belonging)
                            <span class="bg-indigo-500/10 text-indigo-400 px-2 py-1 rounded-lg">Communaute</span>
                        @endif
                        @if ($app->user_recognition)
                            <span class="bg-purple-500/10 text-purple-400 px-2 py-1 rounded-lg">Mise en avant</span>
                        @endif
                        @if ($app->market_size)
                            <span class="bg-white/5 text-gray-400 px-2 py-1 rounded-lg">{{ ucfirst($app->market_size) }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="glass rounded-2xl p-12 text-center">
                <div class="text-4xl mb-4">🔍</div>
                <p class="text-gray-400">Aucune app trouvee. Le scraping tourne automatiquement toutes les 6h.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $apps->links() }}
    </div>

    <script>
        function setFolder(appId, folder, el) {
            const alpine = Alpine.$data(el.closest('[x-data]'));
            const newFolder = alpine.current === folder ? null : folder;

            fetch(`/app/${appId}/folder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ folder: newFolder }),
            })
            .then(r => r.json())
            .then(data => {
                alpine.current = data.folder || '';
            });
        }
    </script>
@endsection
