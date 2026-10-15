<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'email_verified_at',
        'phone_verified_at',
        'is_verified',
        'is_active',
        'is_banned',
        'ban_reason',
        'banned_at',
        'authority_id',
        'auth_provider',
        'google_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_banned' => 'boolean',
        'banned_at' => 'datetime',
    ];

    // JWT Methods
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    // Relations
    public function reports()
    {
        return $this->hasMany(Report::class, 'citizen_id');
    }

    public function uploadedImages()
    {
        return $this->hasMany(ReportImage::class, 'uploaded_by');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_banned', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeCitizens($query)
    {
        return $query->where('role', 'citizen');
    }

    public function scopeAgents($query)
    {
        return $query->where('role', 'agent');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // Helpers
    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAuthority(): bool
    {
        return $this->role === 'authority';
    }

    public function authority()
    {
        return $this->belongsTo(\App\Models\Authority::class);
    }

    public function scopeAuthorities($query)
    {
        return $query->where('role', 'authority');
    }

    public function canReport(): bool
    {
        return $this->is_active && !$this->is_banned && $this->is_verified;
    }

    public function ban(string $reason): void
    {
        $this->update([
            'is_banned' => true,
            'ban_reason' => $reason,
            'banned_at' => now(),
        ]);
    }

    public function unban(): void
    {
        $this->update([
            'is_banned' => false,
            'ban_reason' => null,
            'banned_at' => null,
        ]);
    }
}
