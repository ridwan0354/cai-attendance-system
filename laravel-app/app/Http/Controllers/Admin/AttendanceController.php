<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['participant.group', 'session']);

        // Search by participant name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('participant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by session
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->input('session_id'));
        }

        // Filter by group
        if ($request->filled('group_id')) {
            $groupId = $request->input('group_id');
            $query->whereHas('participant', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        // Filter by method
        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        $attendances = $query->orderBy('check_in_time', 'desc')
            ->paginate(25)
            ->withQueryString();

        // Statistics
        $totalAttendances = Attendance::count();
        $faceCount = Attendance::where('method', 'face')->count();
        $qrCount = Attendance::where('method', 'qr')->count();
        $manualCount = Attendance::where('method', 'manual')->count();

        $sessions = Session::orderBy('day_number')->orderBy('start_time')->get();
        $groups = Group::orderBy('name')->get();
        $participants = Participant::orderBy('name')->get(['id', 'name', 'group_id']);

        return view('admin.attendances.index', compact(
            'attendances',
            'sessions',
            'groups',
            'participants',
            'totalAttendances',
            'faceCount',
            'qrCount',
            'manualCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'session_id'     => 'required|exists:event_sessions,id',
            'notes'          => 'nullable|string|max:255',
        ]);

        $existing = Attendance::where('participant_id', $request->participant_id)
            ->where('session_id', $request->session_id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('warning', 'Peserta ini sudah tercatat hadir pada sesi tersebut.');
        }

        Attendance::create([
            'participant_id'   => $request->participant_id,
            'session_id'       => $request->session_id,
            'check_in_time'    => now(),
            'method'           => 'manual',
            'confidence_score' => null,
            'notes'            => $request->notes,
        ]);

        return redirect()->back()->with('success', 'Log absensi manual berhasil ditambahkan.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'session_id' => 'required|exists:event_sessions,id',
            'notes'      => 'nullable|string|max:255',
        ]);

        $sessionId = $request->input('session_id');

        // Check if participant already has attendance log for target session
        $duplicate = Attendance::where('participant_id', $attendance->participant_id)
            ->where('session_id', $sessionId)
            ->where('id', '!=', $attendance->id)
            ->first();

        if ($duplicate) {
            return redirect()->back()->with('warning', 'Peserta ini sudah tercatat hadir pada sesi tujuan tersebut.');
        }

        $attendance->update([
            'session_id' => $sessionId,
            'notes'      => $request->filled('notes') ? $request->notes : $attendance->notes,
        ]);

        return redirect()->back()->with('success', 'Sesi absensi peserta berhasil dipindahkan.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return redirect()->back()->with('success', 'Log absensi berhasil dihapus.');
    }
}
