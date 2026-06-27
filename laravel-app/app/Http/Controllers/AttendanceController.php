<?php

namespace App\Http\Controllers;

use App\Events\AttendanceRecorded;
use App\Models\Attendance;
use App\Models\Participant;
use App\Models\Session;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    /**
     * Process face recognition from scanner.
     * Called every ~1.5s from the webcam scanner page.
     *
     * POST /api/attendance/face
     */
    public function processFace(Request $request): JsonResponse
    {
        $request->validate([
            'image'      => 'required|string',
            'session_id' => 'nullable|integer|exists:event_sessions,id',
        ]);

        // Get active session if not specified
        $sessionId = $request->input('session_id');
        if (!$sessionId) {
            $activeSession = Session::getActive();
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada sesi aktif. Aktifkan sesi terlebih dahulu.',
                ], 422);
            }
            $sessionId = $activeSession->id;
        }

        // Send to Python DeepFace service
        $result = $this->faceService->recognize($request->input('image'), $sessionId);

        if (!$result['success'] || empty($result['matches'])) {
            return response()->json([
                'success'     => true,
                'recognized'  => false,
                'faces_found' => $result['faces_found'] ?? 0,
                'matches'     => [],
            ]);
        }

        // Process each matched face
        $recorded = [];
        foreach ($result['matches'] as $match) {
            $outcome = $this->recordAttendance(
                participantId:   $match['participant_id'],
                sessionId:       $sessionId,
                confidenceScore: $match['confidence'],
                method:          'face'
            );

            if ($outcome) {
                $recorded[] = $outcome;
            }
        }

        return response()->json([
            'success'     => true,
            'recognized'  => !empty($recorded),
            'faces_found' => $result['faces_found'],
            'matches'     => $recorded,
        ]);
    }

    /**
     * Manual attendance entry (admin override).
     *
     * POST /api/attendance/manual
     */
    public function processManual(Request $request): JsonResponse
    {
        $request->validate([
            'participant_id' => 'required|integer|exists:participants,id',
            'session_id'     => 'required|integer|exists:event_sessions,id',
            'notes'          => 'nullable|string|max:255',
        ]);

        $outcome = $this->recordAttendance(
            participantId:   $request->participant_id,
            sessionId:       $request->session_id,
            confidenceScore: null,
            method:          'manual',
            notes:           $request->notes
        );

        if (!$outcome) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta sudah tercatat hadir di sesi ini.',
            ], 409);
        }

        return response()->json(['success' => true, 'attendance' => $outcome]);
    }

    /**
     * Get attendance list for a session.
     *
     * GET /api/attendance/{sessionId}
     */
    public function index(int $sessionId): JsonResponse
    {
        $attendances = Attendance::where('session_id', $sessionId)
            ->with(['participant.group'])
            ->orderBy('check_in_time', 'desc')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'participant_name' => $a->participant->name,
                'group_name'       => $a->participant->group->name,
                'group_color'      => $a->participant->group->color,
                'check_in_time'    => $a->check_in_time->format('H:i:s'),
                'method'           => $a->method,
                'confidence_score' => $a->confidence_score,
            ]);

        return response()->json(['success' => true, 'data' => $attendances]);
    }

    /**
     * Record attendance, broadcast, and return result.
     * Returns null if participant already marked for this session.
     */
    private function recordAttendance(
        int     $participantId,
        int     $sessionId,
        ?float  $confidenceScore,
        string  $method,
        ?string $notes = null
    ): ?array {
        $participant = Participant::with('group')->find($participantId);
        if (!$participant) {
            return null;
        }

        // Check duplicate
        $existing = Attendance::where('participant_id', $participantId)
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            Log::info("Duplicate attendance: participant {$participantId}, session {$sessionId}");
            return [
                'participant_id'   => $participantId,
                'participant_name' => $participant->name,
                'group_name'       => $participant->group->name,
                'group_color'      => $participant->group->color,
                'check_in_time'    => $existing->check_in_time->format('H:i:s'),
                'method'           => $existing->method,
                'confidence_score' => $existing->confidence_score,
                'already_present'  => true,
            ];
        }

        DB::beginTransaction();
        try {
            $attendance = Attendance::create([
                'participant_id'  => $participantId,
                'session_id'      => $sessionId,
                'check_in_time'   => now(),
                'method'          => $method,
                'confidence_score'=> $confidenceScore,
                'notes'           => $notes,
            ]);

            // Broadcast via Reverb WebSocket
            broadcast(new AttendanceRecorded($attendance));

            DB::commit();

            // Dispatch WhatsApp Check-in Confirmation after response
            \App\Jobs\SendCheckInConfirmation::dispatchAfterResponse($attendance);

            Log::info("Attendance recorded: {$participant->name} [{$method}] session {$sessionId}");

            return [
                'participant_id'   => $participantId,
                'participant_name' => $participant->name,
                'group_name'       => $participant->group->name,
                'group_color'      => $participant->group->color,
                'check_in_time'    => $attendance->check_in_time->format('H:i:s'),
                'method'           => $method,
                'confidence_score' => $confidenceScore,
                'already_present'  => false,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record attendance', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
