<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CategoryAuthorityMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'authority_id',
        'region_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // ==========================================================
    //  RELATIONS
    // ==========================================================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    // ==========================================================
    //  SCOPES
    // ==========================================================

    /**
     * Mappings valables pour une région donnée (ou sans région = toutes régions).
     */
    public function scopeForRegion(Builder $query, ?string $regionName): Builder
    {
        return $query->where(function ($q) use ($regionName) {
            $q->where('region_name', $regionName)
                ->orWhereNull('region_name');
        });
    }

    /**
     * Mapping par défaut pour une catégorie (utilisé quand aucune région ne correspond).
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
