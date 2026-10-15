<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    protected $fillable = [
        'agent_id', 'report_id', 'authority_id',
        'status', 'notes', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agent()    { return $this->belongsTo(User::class, 'agent_id'); }
    public function report()   { return $this->belongsTo(Report::class); }
    public function authority(){ return $this->belongsTo(Authority::class); }
}
