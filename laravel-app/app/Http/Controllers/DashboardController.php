<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Session;
use App\Models\Participant;
use App\Models\Attendance;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    /**
     * Main realtime dashboard view.
     */
    public function index()
    {
        $activeSession = Session::getActive();
        $sessions = Session::orderBy('day_number')->orderBy('start_time')->get();
        $groups = Group::withCount('participants')->get();
        $faceServiceHealthy = $this->faceService->isHealthy();

        $totalMale = Participant::where('gender', 'Laki-laki')->count();
        $totalFemale = Participant::where('gender', 'Perempuan')->count();
        $totalParticipants = Participant::count();

        return view('dashboard.index', compact(
            'activeSession', 'sessions', 'groups', 'faceServiceHealthy',
            'totalMale', 'totalFemale', 'totalParticipants'
        ));
    }

    /**
     * Get live stats for the active session (JSON).
     *
     * GET /api/dashboard/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');
        $session = $sessionId
            ? Session::find($sessionId)
            : Session::getActive();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'No active session']);
        }

        $totalParticipants = Participant::count();
        $totalPresent = Attendance::where('session_id', $session->id)->count();

        $groups = Group::with(['participants.attendances' => function ($q) use ($session) {
            $q->where('session_id', $session->id);
        }])->get()->map(function ($group) use ($session) {
            $stats = $group->getAttendanceStats($session->id);
            return [
                'id'         => $group->id,
                'name'       => $group->name,
                'color'      => $group->color,
                'region'     => $group->region_code,
                'total'      => $stats['total'],
                'present'    => $stats['present'],
                'absent'     => $stats['absent'],
                'percentage' => $stats['percentage'],
            ];
        });

        // Recent check-ins (last 20)
        $recentAttendances = Attendance::where('session_id', $session->id)
            ->with(['participant.group'])
            ->orderBy('check_in_time', 'desc')
            ->limit(20)
            ->get()
            ->map(fn($a) => [
                'participant_id'   => $a->participant_id,
                'name'        => $a->participant->name,
                'group'       => $a->participant->group->name,
                'group_color' => $a->participant->group->color,
                'time'        => $a->check_in_time->format('H:i:s'),
                'method'      => $a->method,
            ]);

        $totalMale = Participant::where('gender', 'Laki-laki')->count();
        $totalFemale = Participant::where('gender', 'Perempuan')->count();

        return response()->json([
            'success'           => true,
            'session'           => [
                'id'         => $session->id,
                'name'       => $session->name,
                'day'        => $session->day_number,
                'start_time' => $session->start_time,
                'end_time'   => $session->end_time,
            ],
            'total_participants' => $totalParticipants,
            'total_present'      => $totalPresent,
            'total_absent'       => $totalParticipants - $totalPresent,
            'total_male'         => $totalMale,
            'total_female'       => $totalFemale,
            'percentage'         => $totalParticipants > 0
                ? round(($totalPresent / $totalParticipants) * 100)
                : 0,
            'groups'             => $groups,
            'recent_attendances' => $recentAttendances,
        ]);
    }

    /**
     * Get detailed attendance lists for a specific session.
     *
     * GET /api/dashboard/sessions/{session}/detail
     */
    public function sessionDetail(Request $request, Session $session): JsonResponse
    {
        $groupId = $request->input('group_id');

        // Base queries
        $participantsQuery = Participant::with('group');
        if ($groupId) {
            $participantsQuery->where('group_id', $groupId);
        }
        $participants = $participantsQuery->orderBy('name')->get();

        // Get all attendance for this session
        $attendances = Attendance::where('session_id', $session->id)
            ->get()
            ->keyBy('participant_id');

        $present = [];
        $absent = [];

        foreach ($participants as $p) {
            $att = $attendances->get($p->id);
            if ($att) {
                $present[] = [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'gender'           => $p->gender,
                    'group_name'       => $p->group->name,
                    'group_color'      => $p->group->color,
                    'check_in_time'    => $att->check_in_time->format('H:i:s'),
                    'method'           => $att->method,
                ];
            } else {
                $absent[] = [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'gender'      => $p->gender,
                    'group_name'  => $p->group->name,
                    'group_color' => $p->group->color,
                    'phone'       => $p->phone,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'session' => [
                'id'   => $session->id,
                'name' => $session->name,
            ],
            'present' => $present,
            'absent'  => $absent,
            'stats'   => [
                'total'   => count($present) + count($absent),
                'present' => count($present),
                'absent'  => count($absent),
            ],
        ]);
    }
}
