<?php
// app/Models/Badge.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'category',
        'points_required',
        'reports_required',
        'conditions',
        'rarity',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'reports_required' => 'integer',
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    // Relations
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRarity($query, $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    // Méthodes Helper
    public function canBeEarnedBy(User $user): bool
    {
        // Vérifier si l'utilisateur a déjà le badge
        if ($user->badges()->where('badge_id', $this->id)->exists()) {
            return false;
        }

        // Vérifier les points requis
        if ($this->points_required > 0 && $user->reputation_score < $this->points_required) {
            return false;
        }

        // Vérifier le nombre de signalements requis
        if ($this->reports_required > 0 && $user->reports()->count() < $this->reports_required) {
            return false;
        }

        // Vérifier les conditions personnalisées
        if (!empty($this->conditions)) {
            return $this->checkCustomConditions($user);
        }

        return true;
    }

    protected function checkCustomConditions(User $user): bool
    {
        // Logique personnalisée pour vérifier les conditions
        // À implémenter selon les besoins
        return true;
    }

    public function isCommon(): bool
    {
        return $this->rarity === 'common';
    }

    public function isRare(): bool
    {
        return $this->rarity === 'rare';
    }

    public function isEpic(): bool
    {
        return $this->rarity === 'epic';
    }

    public function isLegendary(): bool
    {
        return $this->rarity === 'legendary';
    }
}
