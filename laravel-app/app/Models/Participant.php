<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'group_id', 'name', 'nik', 'gender', 'phone', 'photo_path',
        'face_registered', 'rfid_code', 'qr_code'
    ];

    protected $casts = [
        'face_registered' => 'boolean',
    ];

    /**
     * Dynamically verify face registration status by checking both DB flag and physical file.
     */
    public function getFaceRegisteredAttribute($value): bool
    {
        $path = base_path('../python-face-service/face_db/' . $this->id . '/photo.jpg');
        return (bool)$value && file_exists($path);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if participant attended a specific session.
     */
    public function hasAttended(int $sessionId): bool
    {
        return $this->attendances()->where('session_id', $sessionId)->exists();
    }

    /**
     * Get attendance record for a specific session.
     */
    public function getAttendance(int $sessionId): ?Attendance
    {
        return $this->attendances()->where('session_id', $sessionId)->first();
    }
}
