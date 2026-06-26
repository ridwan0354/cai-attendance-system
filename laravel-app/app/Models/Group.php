<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'name', 'region_code', 'pembina_name', 'pembina_phone', 'color'
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Get attendance stats for a specific session.
     */
    public function getAttendanceStats(int $sessionId): array
    {
        $total = $this->participants()->count();
        $present = $this->participants()
            ->whereHas('attendances', fn($q) => $q->where('session_id', $sessionId))
            ->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $total - $present,
            'percentage' => $total > 0 ? round(($present / $total) * 100) : 0,
        ];
    }
}
