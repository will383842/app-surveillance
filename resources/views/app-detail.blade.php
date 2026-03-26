@extends('layouts.app')

@section('title', $app->name)

@section('content')
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="text-blue-400 hover:text-blue-300 text-sm">&larr; Retour a la liste</a>
    </div>

    <div class="glass-strong rounded-2xl p-6">
        {{-- Boutons de classement --}}
        <div class="flex gap-2 mb-6" x-data="{ current: '{{ $app->folder }}' }">
            <span class="text-sm text-gray-400 self-center mr-2">Classer :</span>
            <button @click="setFolder({{ $app->id }}, 'top', $el)"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                    :class="current === 'top' ? 'bg-emerald-500 text-white shadow-lg' : 'glass text-gray-400 hover:text-emerald-400'">
                Top a etudier
            </button>
            <button @click="setFolder({{ $app->id }}, 'a_voir', $el)"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                    :class="current === 'a_voir' ? 'bg-amber-500 text-white shadow-lg' : 'glass text-gray-400 hover:text-amber-400'">
                A voir
            </button>
            <button @click="setFolder({{ $app->id }}, 'archive', $el)"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-all"
                    :class="current === 'archive' ? 'bg-gray-500 text-white shadow-lg' : 'glass text-gray-400 hover:text-gray-300'">
                Archiver
            </button>
        </div>

        {{-- Header --}}
        <div class="flex items-start gap-4 mb-6">
            @if ($app->icon_url)
                <img src="{{ $app->icon_url }}" alt="{{ $app->name }}" class="w-16 h-16 rounded-xl">
            @endif
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white">{{ $app->name }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-gray-400">
                    <span class="bg-blue-900/50 text-blue-300 px-2 py-0.5 rounded">{{ $app->category }}</span>
                    <span class="uppercase">{{ $app->platform }}</span>
                    <span>{{ $app->release_date?->format('d/m/Y') }}</span>
                    @if ($app->age_group)
                        <span class="bg-purple-900/50 text-purple-300 px-2 py-0.5 rounded">{{ ucfirst(str_replace('_', ' ', $app->age_group)) }}</span>
                    @endif
                    @if ($app->business_model)
                        <span class="bg-emerald-900/50 text-emerald-300 px-2 py-0.5 rounded">{{ ucfirst($app->business_model) }}</span>
                    @endif
                    <span>via {{ ucfirst(str_replace('_', ' ', $app->source)) }}</span>
                    @if ($app->source_url)
                        <a href="{{ $app->source_url }}" target="_blank" class="text-blue-400 hover:underline">Voir l'original &rarr;</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Scores --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            @php
                $scores = [
                    ['label' => 'Potentiel explosion', 'value' => $app->explosion_score, 'max' => 10],
                    ['label' => 'Buzz possible', 'value' => $app->buzz_score, 'max' => 10],
                    ['label' => 'Viralite', 'value' => $app->k_factor, 'max' => 10],
                    ['label' => 'Retention', 'value' => ucfirst($app->retention_estimate ?? '?'), 'max' => null],
                    ['label' => 'Nb fonctions', 'value' => $app->feature_count ?? '?', 'max' => null],
                ];
            @endphp
            @foreach ($scores as $score)
                <div class="glass rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-400 mb-1">{{ $score['label'] }}</div>
                    @if ($score['max'])
                        @php $pct = ($score['value'] ?? 0) / $score['max'] * 100; @endphp
                        <div class="text-2xl font-bold {{ $pct >= 70 ? 'text-green-400' : ($pct >= 40 ? 'text-yellow-400' : 'text-red-400') }}">
                            {{ $score['value'] ?? '?' }}/{{ $score['max'] }}
                        </div>
                    @else
                        <div class="text-2xl font-bold text-white">{{ $score['value'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Ce que fait l'app --}}
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white mb-2">Ce que fait l'app</h2>
            <p class="text-gray-300">{{ $app->summary_fr }}</p>
        </div>

        {{-- Experience utilisateur --}}
        @if ($app->experience_fr)
            <div class="glass rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-cyan-400 mb-2">Comment ca se passe quand on l'utilise</h3>
                <p class="text-gray-300 text-sm">{{ $app->experience_fr }}</p>
            </div>
        @endif

        {{-- Grid infos principales --}}
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="glass rounded-lg p-4">
                <h3 class="text-sm font-semibold text-yellow-400 mb-2">Ce qui est unique</h3>
                <p class="text-gray-300 text-sm">{{ $app->exceptional_factor_fr ?? 'Non analyse' }}</p>
            </div>

            <div class="glass rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-400 mb-2">Pour qui c'est fait</h3>
                <p class="text-gray-300 text-sm">{{ $app->target_audience_fr ?? 'Non analyse' }}</p>
            </div>

            <div class="glass rounded-lg p-4">
                <h3 class="text-sm font-semibold text-green-400 mb-2">Points forts</h3>
                @if (is_array($app->pros_fr))
                    <ul class="text-sm text-gray-300 space-y-1">
                        @foreach ($app->pros_fr as $pro)
                            <li class="flex items-start gap-1"><span class="text-green-400">+</span> {{ $pro }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Non analyse</p>
                @endif
            </div>

            <div class="glass rounded-lg p-4">
                <h3 class="text-sm font-semibold text-red-400 mb-2">Points faibles</h3>
                @if (is_array($app->cons_fr))
                    <ul class="text-sm text-gray-300 space-y-1">
                        @foreach ($app->cons_fr as $con)
                            <li class="flex items-start gap-1"><span class="text-red-400">-</span> {{ $con }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Non analyse</p>
                @endif
            </div>
        </div>

        {{-- Liste des fonctions --}}
        @if (is_array($app->features_list_fr) && count($app->features_list_fr))
            <div class="glass rounded-lg p-4 mb-6">
                <h3 class="text-sm font-semibold text-indigo-400 mb-2">Les fonctions de l'app ({{ count($app->features_list_fr) }})</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($app->features_list_fr as $feature)
                        <span class="bg-white/5 text-gray-300 px-3 py-1 rounded text-sm">{{ $feature }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Verdict explosion --}}
        <div class="glass rounded-lg p-4 mb-6 border-l-4 border-purple-500">
            <h3 class="text-sm font-semibold text-purple-400 mb-2">Verdict : est-ce que ca peut exploser ?</h3>
            <p class="text-gray-300 text-sm">{{ $app->explosion_verdict_fr ?? 'Non analyse' }}</p>
        </div>

        {{-- Details engagement --}}
        <h2 class="text-lg font-semibold text-white mb-3">Comment l'app engage les utilisateurs</h2>
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            @if ($app->retention_why_fr)
                <div class="bg-white/5 rounded p-4">
                    <div class="text-xs text-gray-500 mb-1">Pourquoi les gens reviennent (ou pas)</div>
                    <div class="text-sm text-gray-300">{{ $app->retention_why_fr }}</div>
                </div>
            @endif

            @if ($app->sharing_mechanisms_fr)
                <div class="bg-white/5 rounded p-4">
                    <div class="text-xs text-gray-500 mb-1">Comment ca se partage</div>
                    <div class="text-sm text-gray-300">{{ $app->sharing_mechanisms_fr }}</div>
                </div>
            @endif

            <div class="bg-white/5 rounded p-4">
                <div class="text-xs text-gray-500 mb-1">Sentiment de communaute</div>
                <div class="text-sm text-gray-300">
                    @if ($app->group_belonging)
                        <span class="text-blue-400">Oui</span> — {{ $app->group_belonging_detail_fr }}
                    @else
                        <span class="text-gray-500">Non, pas de sentiment de groupe</span>
                    @endif
                </div>
            </div>

            <div class="bg-white/5 rounded p-4">
                <div class="text-xs text-gray-500 mb-1">L'utilisateur est mis en avant ?</div>
                <div class="text-sm text-gray-300">
                    @if ($app->user_recognition)
                        <span class="text-purple-400">Oui</span> — {{ $app->user_recognition_detail_fr }}
                    @else
                        <span class="text-gray-500">Non, pas de mise en avant</span>
                    @endif
                </div>
            </div>

            @if ($app->usage_detail_fr)
                <div class="bg-white/5 rounded p-4">
                    <div class="text-xs text-gray-500 mb-1">Temps passe sur l'app</div>
                    <div class="text-sm text-gray-300">{{ $app->usage_detail_fr }}</div>
                </div>
            @endif

            @if ($app->competition_detail_fr)
                <div class="bg-white/5 rounded p-4">
                    <div class="text-xs text-gray-500 mb-1">Concurrents et differences</div>
                    <div class="text-sm text-gray-300">{{ $app->competition_detail_fr }}</div>
                </div>
            @endif
        </div>

        {{-- Infos marche --}}
        <div class="grid md:grid-cols-3 gap-4">
            @php
                $infos = [
                    ['label' => 'Modele economique', 'value' => ucfirst($app->business_model ?? '?')],
                    ['label' => 'Concurrence', 'value' => ucfirst($app->competition_level ?? '?')],
                    ['label' => 'Difficulte technique pour copier', 'value' => ucfirst($app->technical_effort ?? '?')],
                    ['label' => 'Taille du marche', 'value' => ucfirst($app->market_size ?? '?')],
                    ['label' => 'Duree par session', 'value' => ucfirst($app->usage_duration ?? '?')],
                    ['label' => 'Retention', 'value' => ucfirst($app->retention_estimate ?? '?')],
                ];
            @endphp
            @foreach ($infos as $info)
                <div class="bg-white/5 rounded p-3">
                    <div class="text-xs text-gray-500 mb-1">{{ $info['label'] }}</div>
                    <div class="text-sm text-gray-300 font-medium">{{ $info['value'] }}</div>
                </div>
            @endforeach
        </div>
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
