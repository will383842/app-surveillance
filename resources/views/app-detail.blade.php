@extends('layouts.app')

@section('title', $app->name)

@section('content')
    <div class="mb-4">
        <a href="{{ url()->previous() }}" class="text-blue-400 hover:text-blue-300 text-sm">&larr; Retour</a>
    </div>

    <div class="bg-dark-800 rounded-lg p-6">
        {{-- Header --}}
        <div class="flex items-start gap-4 mb-6">
            @if ($app->icon_url)
                <img src="{{ $app->icon_url }}" alt="{{ $app->name }}" class="w-16 h-16 rounded-xl">
            @endif
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-white">{{ $app->name }}</h1>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-400">
                    <span class="bg-blue-900/50 text-blue-300 px-2 py-0.5 rounded">{{ $app->category }}</span>
                    <span class="uppercase">{{ $app->platform }}</span>
                    <span>{{ $app->release_date?->format('d/m/Y') }}</span>
                    <span>via {{ ucfirst(str_replace('_', ' ', $app->source)) }}</span>
                    @if ($app->source_url)
                        <a href="{{ $app->source_url }}" target="_blank" class="text-blue-400 hover:underline">Voir &rarr;</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Scores --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            @php
                $scores = [
                    ['label' => 'Explosion', 'value' => $app->explosion_score, 'max' => 10],
                    ['label' => 'Buzz', 'value' => $app->buzz_score, 'max' => 10],
                    ['label' => 'K-factor', 'value' => $app->k_factor, 'max' => 10],
                    ['label' => 'Retention', 'value' => $app->retention_estimate, 'max' => null],
                    ['label' => 'Features', 'value' => $app->feature_count, 'max' => null],
                ];
            @endphp
            @foreach ($scores as $score)
                <div class="bg-dark-700 rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-400 mb-1">{{ $score['label'] }}</div>
                    @if ($score['max'])
                        @php $pct = ($score['value'] ?? 0) / $score['max'] * 100; @endphp
                        <div class="text-2xl font-bold {{ $pct >= 70 ? 'text-green-400' : ($pct >= 40 ? 'text-yellow-400' : 'text-red-400') }}">
                            {{ $score['value'] ?? '?' }}/{{ $score['max'] }}
                        </div>
                    @else
                        <div class="text-2xl font-bold text-white">{{ ucfirst($score['value'] ?? '?') }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Resume --}}
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white mb-2">Resume</h2>
            <p class="text-gray-300">{{ $app->summary_fr }}</p>
        </div>

        {{-- Grid infos --}}
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            {{-- Ce qui est exceptionnel --}}
            <div class="bg-dark-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-yellow-400 mb-2">Ce qui est exceptionnel</h3>
                <p class="text-gray-300 text-sm">{{ $app->exceptional_factor_fr ?? 'Non analyse' }}</p>
            </div>

            {{-- Cible clients --}}
            <div class="bg-dark-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-400 mb-2">Cible clients</h3>
                <p class="text-gray-300 text-sm">{{ $app->target_audience_fr ?? 'Non analyse' }}</p>
            </div>

            {{-- Points positifs --}}
            <div class="bg-dark-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-green-400 mb-2">Points positifs</h3>
                @if (is_array($app->pros_fr))
                    <ul class="text-sm text-gray-300 space-y-1">
                        @foreach ($app->pros_fr as $pro)
                            <li>+ {{ $pro }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Non analyse</p>
                @endif
            </div>

            {{-- Points negatifs --}}
            <div class="bg-dark-700 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-red-400 mb-2">Points negatifs</h3>
                @if (is_array($app->cons_fr))
                    <ul class="text-sm text-gray-300 space-y-1">
                        @foreach ($app->cons_fr as $con)
                            <li>- {{ $con }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">Non analyse</p>
                @endif
            </div>
        </div>

        {{-- Verdict explosion --}}
        <div class="bg-dark-700 rounded-lg p-4 mb-6">
            <h3 class="text-sm font-semibold text-purple-400 mb-2">Verdict — Potentiel d'explosion</h3>
            <p class="text-gray-300 text-sm">{{ $app->explosion_verdict_fr ?? 'Non analyse' }}</p>
        </div>

        {{-- Details supplementaires --}}
        <div class="grid md:grid-cols-3 gap-4">
            @php
                $details = [
                    ['label' => 'Modele economique', 'value' => ucfirst($app->business_model ?? '?')],
                    ['label' => 'Concurrence', 'value' => ucfirst($app->competition_level ?? '?')],
                    ['label' => 'Effort technique', 'value' => ucfirst($app->technical_effort ?? '?')],
                    ['label' => 'Taille du marche', 'value' => ucfirst($app->market_size ?? '?')],
                    ['label' => 'Duree d\'usage', 'value' => ucfirst($app->usage_duration ?? '?')],
                    ['label' => 'Mecanismes de partage', 'value' => $app->sharing_mechanisms_fr ?? '?'],
                    ['label' => 'Appartenance groupe', 'value' => $app->group_belonging ? 'Oui — ' . $app->group_belonging_detail_fr : 'Non'],
                    ['label' => 'Reconnaissance user', 'value' => $app->user_recognition ? 'Oui — ' . $app->user_recognition_detail_fr : 'Non'],
                ];
            @endphp
            @foreach ($details as $detail)
                <div class="bg-dark-900 rounded p-3">
                    <div class="text-xs text-gray-500 mb-1">{{ $detail['label'] }}</div>
                    <div class="text-sm text-gray-300">{{ $detail['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
