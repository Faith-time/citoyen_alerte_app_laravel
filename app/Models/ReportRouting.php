<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportRouting extends Model
{
    use HasFactory;

    protected $table = 'report_routing';

    protected $fillable = [
        'report_id',
        'authority_id',
        'status',
        'routed_at',
        'resolved_at',
        'notes',
    ];

    protected $casts = [
        'routed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relations
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function authority()
    {
        return $this->belongsTo(Authority::class);
    }

    // Scopes
    public function scopePending($query)
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

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('routed_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'routed' => 'Routé',
            'in_progress' => 'En cours',
            'resolved' => 'Résolu',
            default => $this->status,
        };
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->resolved_at) {
            return $this->routed_at->diffInHours($this->resolved_at);
        }
        return $this->routed_at->diffInHours(now());
    }

    // Helpers
    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function markAsResolved(?string $notes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'routed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($routing) {
            if (!$routing->routed_at) {
                $routing->routed_at = now();
            }
        });
    }
}
