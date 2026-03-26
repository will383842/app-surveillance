<?php

namespace App\Services;

use App\Models\DiscoveredApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAnalyzer
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-6';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
    }

    /**
     * Analyse une app découverte et remplit toutes les colonnes d'analyse.
     */
    public function analyze(DiscoveredApp $app): bool
    {
        if (!$this->apiKey) {
            Log::error('ClaudeAnalyzer: ANTHROPIC_API_KEY non configuré');
            return false;
        }

        try {
            $prompt = $this->buildPrompt($app);

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->ok()) {
                Log::error('Claude API error: ' . $response->status() . ' - ' . $response->body());
                return false;
            }

            $text = data_get($response->json(), 'content.0.text', '');
            return $this->parseAndUpdate($app, $text);
        } catch (\Exception $e) {
            Log::error("ClaudeAnalyzer [{$app->name}]: " . $e->getMessage());
            return false;
        }
    }

    private function buildPrompt(DiscoveredApp $app): string
    {
        $info = "Nom: {$app->name}";
        if ($app->summary_fr) $info .= "\nDescription: {$app->summary_fr}";
        if ($app->category) $info .= "\nCatégorie: {$app->category}";
        if ($app->platform) $info .= "\nPlateforme: {$app->platform}";
        if ($app->source_url) $info .= "\nURL: {$app->source_url}";
        if ($app->release_date) $info .= "\nDate sortie: {$app->release_date->format('Y-m-d')}";

        return <<<PROMPT
Tu es un analyste expert en applications mobiles et web. Analyse cette application et réponds UNIQUEMENT en JSON valide, tout en français.

{$info}

Réponds avec ce JSON exact (pas de markdown, pas de commentaires) :
{
    "summary_fr": "Résumé en 2-3 phrases de ce que fait l'app",
    "category": "Catégorie principale (Productivité, Social, Santé, Finance, Éducation, Divertissement, Utilitaires, Communication, Shopping, Voyage, Alimentation, Musique, Photo & Vidéo, Business, Lifestyle, Actualités, Sport, Rencontres, Médical, Intelligence Artificielle, Jeux, Design, Marketing, Développement, Crypto, Fitness, Météo, Navigation, Référence, Autre)",
    "feature_count": 5,
    "exceptional_factor_fr": "Ce qui rend cette app unique/exceptionnelle par rapport à la concurrence",
    "target_audience_fr": "Cible clients précise (âge, profil, besoin)",
    "business_model": "freemium|subscription|ads|paid|free",
    "pros_fr": ["Point positif 1", "Point positif 2", "Point positif 3"],
    "cons_fr": ["Point négatif 1", "Point négatif 2", "Point négatif 3"],
    "explosion_score": 7,
    "explosion_verdict_fr": "Explication argumentée du potentiel d'explosion (timing marché, besoin réel, différenciation, viralité naturelle)",
    "buzz_score": 6,
    "retention_estimate": "faible|moyenne|forte",
    "k_factor": 5,
    "sharing_mechanisms_fr": "Mécanismes de partage identifiés (invitations, contenu partageable, referral, etc.)",
    "group_belonging": true,
    "group_belonging_detail_fr": "Comment l'app crée un sentiment d'appartenance",
    "usage_duration": "courte|moyenne|longue",
    "user_recognition": false,
    "user_recognition_detail_fr": "Comment l'utilisateur est reconnu ou mis en avant",
    "competition_level": "faible|moyenne|saturée",
    "technical_effort": "facile|moyen|complexe",
    "market_size": "niche|moyen|massif"
}

IMPORTANT:
- explosion_score, buzz_score, k_factor sont des entiers de 0 à 10
- Sois honnête et critique dans ton analyse
- Si tu manques d'infos, fais ta meilleure estimation basée sur le nom et la description
- TOUT en français
PROMPT;
    }

    private function parseAndUpdate(DiscoveredApp $app, string $text): bool
    {
        // Extraire le JSON de la réponse
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\n?/', '', $text);
            $text = preg_replace('/\n?```$/', '', $text);
        }

        $data = json_decode($text, true);
        if (!$data) {
            Log::error("ClaudeAnalyzer: JSON invalide pour [{$app->name}]");
            return false;
        }

        $app->update([
            'summary_fr' => $data['summary_fr'] ?? $app->summary_fr,
            'category' => $data['category'] ?? $app->category,
            'feature_count' => $data['feature_count'] ?? null,
            'exceptional_factor_fr' => $data['exceptional_factor_fr'] ?? null,
            'target_audience_fr' => $data['target_audience_fr'] ?? null,
            'business_model' => $data['business_model'] ?? null,
            'pros_fr' => $data['pros_fr'] ?? null,
            'cons_fr' => $data['cons_fr'] ?? null,
            'explosion_score' => min(10, max(0, intval($data['explosion_score'] ?? 0))),
            'explosion_verdict_fr' => $data['explosion_verdict_fr'] ?? null,
            'buzz_score' => min(10, max(0, intval($data['buzz_score'] ?? 0))),
            'retention_estimate' => $data['retention_estimate'] ?? null,
            'k_factor' => min(10, max(0, intval($data['k_factor'] ?? 0))),
            'sharing_mechanisms_fr' => $data['sharing_mechanisms_fr'] ?? null,
            'group_belonging' => $data['group_belonging'] ?? null,
            'group_belonging_detail_fr' => $data['group_belonging_detail_fr'] ?? null,
            'usage_duration' => $data['usage_duration'] ?? null,
            'user_recognition' => $data['user_recognition'] ?? null,
            'user_recognition_detail_fr' => $data['user_recognition_detail_fr'] ?? null,
            'competition_level' => $data['competition_level'] ?? null,
            'technical_effort' => $data['technical_effort'] ?? null,
            'market_size' => $data['market_size'] ?? null,
            'analyzed' => true,
            'analyzed_at' => now(),
        ]);

        return true;
    }
}
