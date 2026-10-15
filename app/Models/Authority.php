<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Authority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'website',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function routings()
    {
        return $this->hasMany(ReportRouting::class);
    }

    public function reports()
    {
        return $this->hasManyThrough(Report::class, ReportRouting::class, 'authority_id', 'id', 'id', 'report_id');
    }


    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeGovernment($query)
    {
        return $query->where('type', 'government');
    }

    public function scopeMunicipality($query)
    {
        return $query->where('type', 'municipality');
    }

    public function scopePublicWorks($query)
    {
        return $query->where('type', 'public_works');
    }

    public function scopeUtility($query)
    {
        return $query->where('type', 'utility');
    }

    public function scopeNgo($query)
    {
        return $query->where('type', 'ngo');
    }

    // Helpers
    public function getPendingRoutingsCount(): int
    {
        return $this->routings()->where('status', 'routed')->count();
    }

    public function getInProgressRoutingsCount(): int
    {
        return $this->routings()->where('status', 'in_progress')->count();
    }

    public function getResolvedRoutingsCount(): int
    {
        return $this->routings()->where('status', 'resolved')->count();
    }

    protected static function booted(): void
    {
        static::created(function (Authority $authority) {
            // Générer un email propre depuis le nom
            $slug = strtolower(str_replace([' ', '/', "'", ''], ['.', '.', '', ''], $authority->name));

            $email = $slug . '@citoyen-alerte.sn';

            // Éviter les doublons d'email
            if (\App\Models\User::where('email', $email)->exists()) {
                $email = $slug . '.' . $authority->id . '@citoyen-alerte.sn';
            }

            \App\Models\User::create([
                'name'         => $authority->name,
                'email'        => $email,
                'password'     => bcrypt('password123'),
                'role'         => 'authority',
                'authority_id' => $authority->id,
                'is_active'    => true,
                'is_verified'  => true,
            ]);
        });
    }}
