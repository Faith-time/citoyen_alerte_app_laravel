<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportImage extends Model
{
    use HasFactory;

    // $timestamps = true par défaut — created_at et updated_at gérés automatiquement

    protected $fillable = [
        'report_id',
        'image_path',
        'type',
        'uploaded_by',
        'file_size',
        'mime_type',
        'is_validated',
        'ai_confidence_score',
        'ai_flags',
    ];

    protected $casts = [
        'is_validated'        => 'boolean',
        'ai_confidence_score' => 'decimal:2',
        'ai_flags'            => 'json',
    ];


    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeInitial($query)
    {
        return $query->where('type', 'initial');
    }

    public function scopeBefore($query)
    {
        return $query->where('type', 'before');
    }

    public function scopeAfter($query)
    {
        return $query->where('type', 'after');
    }

    public function scopeEvidence($query)
    {
        return $query->where('type', 'evidence');
    }

    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk('public')->path($this->image_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Supprime le fichier physique ET l'enregistrement en base.
     */
    public function delete(): bool
    {
        if (Storage::disk('public')->exists($this->image_path)) {
            Storage::disk('public')->delete($this->image_path);
        }

        return parent::delete();
    }

    /**
     * Marque l'image comme validée.
     */
    public function validate(): void
    {
        $this->update(['is_validated' => true]);
    }
}
