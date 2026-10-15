<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'citizen_id',
        'category_id',
        'reference',
        'title',
        'description',
        'latitude',
        'longitude',
        'address',
        'commune_name',
        'region_name',
        'severity',
        'status',
        'is_anonymous',
        'resolved_at',
    ];

    protected $casts = [
        'latitude'    => 'decimal:8',
        'longitude'   => 'decimal:8',
        'is_anonymous' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // =========================================================================
    // Relations
    // =========================================================================

    public function citizen()
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ReportImage::class);
    }

    public function routings()
    {
        return $this->hasMany(ReportRouting::class);
    }

    public function currentRouting()
    {
        return $this->hasOne(ReportRouting::class)->latest();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRouted($query)
    {
        return $query->where('status', 'routed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByCommune($query, string $commune)
    {
        return $query->where('commune_name', $commune);
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region_name', $region);
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm = 5)
    {
        return $query->selectRaw("
            *,
            ( 6371 * acos( cos( radians(?) ) *
              cos( radians( latitude ) ) *
              cos( radians( longitude ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( latitude ) )
            ) ) AS distance
        ", [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getLocationAttribute(): array
    {
        return [
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            'address'   => $this->address,
            'commune'   => $this->commune_name,
            'region'    => $this->region_name,
        ];
    }

    public function getIsUrgentAttribute(): bool
    {
        return $this->severity === 'high';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'     => 'En attente',
            'routed'      => 'Routé',
            'in_progress' => 'En cours',
            'resolved'    => 'Résolu',
            default       => $this->status,
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'low'    => 'Faible',
            'medium' => 'Moyen',
            'high'   => 'Élevé',
            default  => $this->severity,
        };
    }

    // =========================================================================
    // Boot — génération de la référence unique
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (!$report->reference) {
                $report->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year     = now()->year;
        $prefix   = "REP-{$year}-";
        $attempts = 0;

        do {
            // Récupère la référence la plus haute de l'année en cours
            $last = static::whereYear('created_at', $year)
                ->where('reference', 'like', $prefix . '%')
                ->max('reference');   // ex: "REP-2026-00004"  ou  null

            if ($last) {
                // Extrait les 5 derniers caractères numériques et incrémente
                $lastNumber = (int) substr($last, -5);
                $next       = $lastNumber + 1;
            } else {
                $next = 1;
            }

            $reference = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
            $attempts++;

            // Vérifie que la référence n'existe pas déjà (sécurité concurrence)
            $exists = static::where('reference', $reference)->exists();

        } while ($exists && $attempts < 5);

        return $reference;
    }
}
