<?php
// app/Models/AppVersion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'platform',
        'version',
        'build_number',
        'is_mandatory',
        'min_supported_version',
        'release_notes',
        'download_url',
        'is_active',
        'released_at',
    ];

    protected $casts = [
        'build_number' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'released_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('build_number', 'desc');
    }

    // Méthodes Helper
    public static function getLatestVersion($platform)
    {
        return static::where('platform', $platform)
            ->where('is_active', true)
            ->orderBy('build_number', 'desc')
            ->first();
    }

    public function isUpdateRequired($currentVersion): bool
    {
        if (!$this->is_mandatory) {
            return false;
        }

        return version_compare($currentVersion, $this->version, '<');
    }

    public function isSupported($currentVersion): bool
    {
        if (empty($this->min_supported_version)) {
            return true;
        }

        return version_compare($currentVersion, $this->min_supported_version, '>=');
    }
}
