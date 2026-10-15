<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentZone extends Model
{
    use HasFactory;

    protected $table = 'agents_zones';

    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'authority_id',
        'commune_id',
        'commune_name',
        'region_name',
        'radius_km',
        'center_lat',
        'center_lng',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'radius_km'  => 'float',
        'center_lat' => 'float',
        'center_lng' => 'float',
        'created_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function authority()
    {
        return $this->belongsTo(Authority::class);
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForAuthority($query, int $authorityId)
    {
        return $query->where('authority_id', $authorityId);
    }

    public function scopeForRegion($query, string $regionName)
    {
        return $query->where('region_name', $regionName);
    }

    public function scopeForCommune($query, string $communeName)
    {
        return $query->where('commune_name', $communeName);
    }

    /**
     * Zones couvrant un point GPS (Haversine via SQL)
     */
    public function scopeCoversPoint($query, float $lat, float $lng)
    {
        return $query->whereNotNull('center_lat')
            ->whereNotNull('center_lng')
            ->whereRaw(
                '(6371 * ACOS(
                             COS(RADIANS(?)) * COS(RADIANS(center_lat)) *
                             COS(RADIANS(center_lng) - RADIANS(?)) +
                             SIN(RADIANS(?)) * SIN(RADIANS(center_lat))
                         )) <= radius_km',
                [$lat, $lng, $lat]
            );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Vérifie si un point GPS est dans le rayon de la zone
     */
    public function coversPoint(float $lat, float $lng): bool
    {
        if (!$this->center_lat || !$this->center_lng) {
            return false;
        }

        $R    = 6371;
        $dLat = deg2rad($lat - $this->center_lat);
        $dLng = deg2rad($lng - $this->center_lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->center_lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        $distance = $R * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $distance <= $this->radius_km;
    }
}
