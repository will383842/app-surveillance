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
                'max_tokens' => 2500,
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
Tu analyses des applications pour le grand public. Ecris comme si tu expliquais a un ami, avec des mots simples et clairs. Pas de jargon technique.

IMPORTANT:
- Si cette app est destinee aux professionnels, entreprises, developpeurs ou B2B, reponds UNIQUEMENT: {"is_consumer": false}
- Sinon, fais l'analyse complete ci-dessous
- TOUT doit etre en francais, y compris le nom traduit de l'app si possible
- Utilise un langage simple, comme si tu parlais a quelqu'un qui ne connait rien a la tech

{$info}

Reponds UNIQUEMENT en JSON valide (pas de markdown, pas de commentaires) :
{
    "is_consumer": true,
    "name_fr": "Nom de l'app traduit en francais si possible, sinon garder le nom original",
    "summary_fr": "Explication simple en 2-3 phrases de ce que fait l'app. Comme si tu expliquais a ta mere. Qu'est-ce que ca fait concretement pour l'utilisateur ?",
    "experience_fr": "Comment ca se passe quand on utilise l'app ? Decris le parcours : tu ouvres l'app, tu fais quoi, tu vois quoi, ca donne quoi ? Est-ce que c'est agreable, rapide, intuitif ?",
    "category": "Categorie (Social, Sante, Divertissement, Photo & Video, Musique, Jeux, Shopping, Rencontres, Fitness, Voyage, Alimentation, Lifestyle, Communication, Finance perso, Education, Sport, Meteo, Autre)",
    "feature_count": 5,
    "features_list_fr": ["Fonction 1 en mots simples", "Fonction 2", "Fonction 3"],
    "exceptional_factor_fr": "En une phrase : qu'est-ce qui rend cette app differente de toutes les autres ? Pourquoi quelqu'un choisirait celle-la ?",
    "target_audience_fr": "A qui c'est destine ? (ex: ados 13-17 ans qui aiment les memes, mamans actives 30-40 ans, etudiants qui veulent economiser...)",
    "age_group": "gen_z|millennials|adultes|seniors|tous",
    "business_model": "gratuit|freemium|abonnement|pub|payant",
    "pros_fr": ["Point fort 1 en langage simple", "Point fort 2", "Point fort 3"],
    "cons_fr": ["Point faible 1 en langage simple", "Point faible 2", "Point faible 3"],
    "explosion_score": 7,
    "explosion_verdict_fr": "Pourquoi cette app peut (ou ne peut pas) exploser ? Explique simplement : est-ce que les gens en ont vraiment besoin ? Est-ce que c'est le bon moment ? Est-ce que ca peut devenir viral ?",
    "buzz_score": 6,
    "retention_estimate": "faible|moyenne|forte",
    "retention_why_fr": "Pourquoi les gens reviendraient (ou pas) sur cette app ? Qu'est-ce qui les accroche ou les fait fuir ?",
    "k_factor": 5,
    "sharing_mechanisms_fr": "Comment les utilisateurs partagent l'app ? (ex: on peut inviter ses amis, le contenu se partage facilement sur Insta/TikTok, il y a un systeme de parrainage...)",
    "group_belonging": true,
    "group_belonging_detail_fr": "Est-ce que l'app donne le sentiment de faire partie d'un groupe ou d'une communaute ? Comment ?",
    "usage_duration": "courte|moyenne|longue",
    "usage_detail_fr": "Combien de temps on passe sur l'app par session ? (ex: 2 min pour checker, 15 min pour scroller, 1h pour jouer)",
    "user_recognition": false,
    "user_recognition_detail_fr": "Est-ce que l'utilisateur se sent important, reconnu, mis en avant ? (ex: profil public, badges, classement, likes sur son contenu...)",
    "competition_level": "faible|moyenne|saturee",
    "competition_detail_fr": "Quelles sont les apps concurrentes connues ? En quoi celle-ci est differente ?",
    "technical_effort": "facile|moyen|complexe",
    "market_size": "niche|moyen|enorme"
}

RAPPEL: Si l'app est pour les pros/entreprises/developpeurs, reponds juste {"is_consumer": false}
Les scores explosion_score, buzz_score, k_factor sont des entiers de 0 a 10.
Sois honnete et critique. Langage SIMPLE.
PROMPT;
    }

    private function parseAndUpdate(DiscoveredApp $app, string $text): bool
    {
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

        // Si app pro/B2B, marquer comme analysée mais la supprimer
        if (isset($data['is_consumer']) && $data['is_consumer'] === false) {
            $app->delete();
            Log::info("ClaudeAnalyzer: [{$app->name}] supprimée (app pro/B2B)");
            return true;
        }

        $app->update([
            'name' => $data['name_fr'] ?? $app->name,
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
            // Nouveaux champs stockés en JSON dans les colonnes existantes ou via extra
            'experience_fr' => $data['experience_fr'] ?? null,
            'retention_why_fr' => $data['retention_why_fr'] ?? null,
            'usage_detail_fr' => $data['usage_detail_fr'] ?? null,
            'competition_detail_fr' => $data['competition_detail_fr'] ?? null,
            'features_list_fr' => $data['features_list_fr'] ?? null,
            'age_group' => $data['age_group'] ?? null,
        ]);

        return true;
    }
}
