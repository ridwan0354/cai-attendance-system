<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'participant_id', 'session_id', 'check_in_time',
        'method', 'confidence_score', 'notes'
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'confidence_score' => 'decimal:2',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
