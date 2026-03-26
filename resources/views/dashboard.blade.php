@extends('layouts.app')

@section('title', 'Dashboard')

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
            {{-- Recherche --}}
            <div class="col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Rechercher une app..."
                       class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none">
            </div>

            {{-- Score minimum --}}
            <div>
                <select name="min_score" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Score min.</option>
                    @for ($i = 5; $i <= 10; $i++)
                        <option value="{{ $i }}" {{ request('min_score') == $i ? 'selected' : '' }}>{{ $i }}+/10</option>
                    @endfor
                </select>
            </div>

            {{-- Categorie --}}
            <div>
                <select name="category" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Categorie</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Plateforme --}}
            <div>
                <select name="platform" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Plateforme</option>
                    @foreach ($platforms as $plat)
                        <option value="{{ $plat }}" {{ request('platform') == $plat ? 'selected' : '' }}>{{ strtoupper($plat) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Source --}}
            <div>
                <select name="source" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Source</option>
                    @foreach ($sources as $src)
                        <option value="{{ $src }}" {{ request('source') == $src ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $src)) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Modele eco --}}
            <div>
                <select name="business_model" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Modele eco.</option>
                    @foreach (['freemium', 'subscription', 'ads', 'paid', 'free'] as $bm)
                        <option value="{{ $bm }}" {{ request('business_model') == $bm ? 'selected' : '' }}>{{ ucfirst($bm) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Taille marche --}}
            <div>
                <select name="market_size" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Marche</option>
                    @foreach (['niche', 'moyen', 'massif'] as $ms)
                        <option value="{{ $ms }}" {{ request('market_size') == $ms ? 'selected' : '' }}>{{ ucfirst($ms) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Concurrence --}}
            <div>
                <select name="competition_level" class="w-full bg-dark-700 border border-dark-700 rounded px-3 py-2 text-sm text-white">
                    <option value="">Concurrence</option>
                    @foreach (['faible', 'moyenne', 'saturée'] as $cl)
                        <option value="{{ $cl }}" {{ request('competition_level') == $cl ? 'selected' : '' }}>{{ ucfirst($cl) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Boutons --}}
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

    {{-- Tableau --}}
    <div class="overflow-x-auto bg-dark-800 rounded-lg">
        <table class="w-full text-sm">
            <thead class="bg-dark-700 text-gray-400 text-left">
                <tr>
                    @php
                        $columns = [
                            'explosion_score' => 'Explosion',
                            'buzz_score' => 'Buzz',
                            'name' => 'App',
                            'category' => 'Categorie',
                            'platform' => 'Plateforme',
                            'k_factor' => 'K-factor',
                            'feature_count' => 'Features',
                            'release_date' => 'Sortie',
                        ];
                    @endphp
                    @foreach ($columns as $col => $label)
                        @php
                            $newDir = ($sortBy === $col && $sortDir === 'desc') ? 'asc' : 'desc';
                            $arrow = $sortBy === $col ? ($sortDir === 'desc' ? ' ↓' : ' ↑') : '';
                        @endphp
                        <th class="px-3 py-3 cursor-pointer hover:text-white whitespace-nowrap">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $col, 'dir' => $newDir]) }}">
                                {{ $label }}{{ $arrow }}
                            </a>
                        </th>
                    @endforeach
                    <th class="px-3 py-3">Resume</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-dark-700">
                @forelse ($apps as $app)
                    <tr class="hover:bg-dark-700 transition-colors cursor-pointer"
                        onclick="window.location='{{ route('app.show', $app) }}'">
                        {{-- Score explosion --}}
                        <td class="px-3 py-3 text-center score-cell {{ $app->explosion_score >= 7 ? 'score-high' : ($app->explosion_score >= 4 ? 'score-mid' : 'score-low') }}">
                            {{ $app->explosion_score }}/10
                        </td>
                        {{-- Score buzz --}}
                        <td class="px-3 py-3 text-center score-cell {{ $app->buzz_score >= 7 ? 'score-high' : ($app->buzz_score >= 4 ? 'score-mid' : 'score-low') }}">
                            {{ $app->buzz_score }}/10
                        </td>
                        {{-- Nom --}}
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                @if ($app->icon_url)
                                    <img src="{{ $app->icon_url }}" alt="" class="w-8 h-8 rounded">
                                @endif
                                <div>
                                    <div class="font-medium text-white">{{ $app->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $app->target_audience_fr }}</div>
                                </div>
                            </div>
                        </td>
                        {{-- Categorie --}}
                        <td class="px-3 py-3">
                            <span class="bg-blue-900/50 text-blue-300 px-2 py-0.5 rounded text-xs">{{ $app->category }}</span>
                        </td>
                        {{-- Plateforme --}}
                        <td class="px-3 py-3 text-xs uppercase">{{ $app->platform }}</td>
                        {{-- K-factor --}}
                        <td class="px-3 py-3 text-center {{ $app->k_factor >= 7 ? 'text-green-400' : ($app->k_factor >= 4 ? 'text-yellow-400' : 'text-red-400') }}">
                            {{ $app->k_factor }}/10
                        </td>
                        {{-- Features --}}
                        <td class="px-3 py-3 text-center">{{ $app->feature_count }}</td>
                        {{-- Date --}}
                        <td class="px-3 py-3 text-xs text-gray-400">{{ $app->release_date?->format('d/m/Y') }}</td>
                        {{-- Resume --}}
                        <td class="px-3 py-3 text-xs text-gray-400 max-w-xs truncate">
                            {{ \Illuminate\Support\Str::limit($app->summary_fr, 80) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-8 text-center text-gray-500">
                            Aucune app trouvee. Lancez <code class="text-blue-400">php artisan apps:scrape</code> pour commencer.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $apps->links() }}
    </div>
@endsection
