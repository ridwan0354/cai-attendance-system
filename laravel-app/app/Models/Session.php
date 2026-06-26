<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $table = 'event_sessions'; // Renamed to avoid conflict with Laravel sessions
    protected $fillable = [
        'name', 'day_number', 'date', 'start_time', 'end_time', 'is_active'
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
        'day_number' => 'integer',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Get the active session.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Check if session ends within given minutes.
     */
    public function endsWithinMinutes(int $minutes): bool
    {
        $endTime = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);
        $diffMinutes = now()->diffInMinutes($endTime, false);

        return $diffMinutes > 0 && $diffMinutes <= $minutes;
    }

    /**
     * Get total attendance count for this session.
     */
    public function getTotalAttendance(): int
    {
        return $this->attendances()->count();
    }
}
