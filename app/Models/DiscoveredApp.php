<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DiscoveredApp extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'release_date' => 'date',
        'analyzed_at' => 'datetime',
        'analyzed' => 'boolean',
        'group_belonging' => 'boolean',
        'user_recognition' => 'boolean',
        'pros_fr' => 'array',
        'cons_fr' => 'array',
        'feature_count' => 'integer',
        'explosion_score' => 'integer',
        'buzz_score' => 'integer',
        'k_factor' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (DiscoveredApp $app) {
            if (empty($app->slug)) {
                $app->slug = Str::slug($app->name) . '-' . Str::random(6);
            }
        });
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('analyzed', true);
    }

    public function scopeTopRated($query)
    {
        return $query->analyzed()->orderByDesc('explosion_score');
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSince2026($query)
    {
        return $query->where('release_date', '>=', '2026-01-01');
    }
}
