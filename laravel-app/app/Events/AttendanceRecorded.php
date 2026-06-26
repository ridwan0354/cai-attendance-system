<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Attendance $attendance
    ) {}

    /**
     * Broadcast on the public attendance channel.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('attendance'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.recorded';
    }

    /**
     * Data pushed to the frontend via WebSocket.
     */
    public function broadcastWith(): array
    {
        $attendance = $this->attendance->load(['participant.group', 'session']);
        $participant = $attendance->participant;

        return [
            'attendance_id'    => $attendance->id,
            'participant_id'   => $participant->id,
            'participant_name' => $participant->name,
            'group_id'         => $participant->group->id,
            'group_name'       => $participant->group->name,
            'group_color'      => $participant->group->color,
            'session_id'       => $attendance->session_id,
            'check_in_time'    => $attendance->check_in_time->format('H:i:s'),
            'method'           => $attendance->method,
            'confidence_score' => $attendance->confidence_score,
        ];
    }
}
