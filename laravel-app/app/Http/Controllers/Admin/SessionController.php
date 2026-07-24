<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::orderBy('day_number')->orderBy('start_time')->get();
        $groups = \App\Models\Group::orderBy('name')->get();
        return view('admin.sessions.index', compact('sessions', 'groups'));
    }

    public function create()
    {
        return view('admin.sessions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'day_number' => 'required|integer|between:1,3',
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        Session::create($validated);
        return redirect()->route('admin.sessions.index')->with('success', 'Sesi berhasil dibuat.');
    }

    public function edit(Session $session)
    {
        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, Session $session)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'day_number' => 'required|integer|between:1,3',
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $session->update($validated);
        return redirect()->route('admin.sessions.index')->with('success', 'Sesi diperbarui.');
    }

    public function destroy(Session $session)
    {
        $name = $session->name;

        DB::transaction(function () use ($session) {
            $session->attendances()->delete();
            $session->notificationLogs()->delete();
            $session->delete();
        });

        return redirect()->route('admin.sessions.index')->with('success', "Sesi '{$name}' berhasil dihapus.");
    }

    public function activate(Session $session)
    {
        $today = now()->format('Y-m-d');
        $sessionDate = $session->date->format('Y-m-d');

        if ($sessionDate !== $today) {
            return back()->with('error', "Gagal mengaktifkan sesi. Tanggal sesi ({$session->date->format('d M Y')}) tidak sesuai dengan tanggal hari ini.");
        }

        $startTime = \Carbon\Carbon::parse($sessionDate . ' ' . $session->start_time);
        $endTime   = \Carbon\Carbon::parse($sessionDate . ' ' . $session->end_time);
        $earliestStart = $startTime->copy()->subHour();

        if (now()->lt($earliestStart)) {
            return back()->with('error', "Gagal mengaktifkan sesi. Sesi ini baru dapat diaktifkan paling cepat 1 jam sebelum jadwal dimulai (mulai pukul {$earliestStart->format('H:i')}).");
        }

        // Deactivate all first, then activate
        Session::where('is_active', true)->update(['is_active' => false]);
        $session->update(['is_active' => true]);

        $msg = now()->gt($endTime)
            ? "Sesi '{$session->name}' diaktifkan (di luar jam sesi — mode override admin)."
            : "Sesi '{$session->name}' diaktifkan.";

        return back()->with('success', $msg);
    }

    /**
     * Halaman kelola absensi per sesi.
     */
    public function attendances(Session $session)
    {
        $attendances = Attendance::where('session_id', $session->id)
            ->with(['participant.group'])
            ->orderBy('check_in_time', 'desc')
            ->get();

        $attendedIds = $attendances->pluck('participant_id');

        $notAttended = Participant::with('group')
            ->whereNotIn('id', $attendedIds)
            ->orderBy('name')
            ->get();

        $groups = Group::orderBy('name')->get();

        return view('admin.sessions.attendances', compact('session', 'attendances', 'notAttended', 'groups'));
    }

    /**
     * Tambah absensi manual untuk peserta ke sesi tertentu.
     */
    public function addAttendance(Request $request, Session $session)
    {
        $request->validate([
            'participant_id' => 'required|integer|exists:participants,id',
        ]);

        $exists = Attendance::where('session_id', $session->id)
            ->where('participant_id', $request->participant_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Peserta sudah tercatat hadir di sesi ini.');
        }

        Attendance::create([
            'participant_id'   => $request->participant_id,
            'session_id'       => $session->id,
            'check_in_time'    => now(),
            'method'           => 'manual',
            'confidence_score' => null,
            'notes'            => 'Input manual oleh admin',
        ]);

        $participant = Participant::find($request->participant_id);
        return back()->with('success', "Absensi {$participant->name} berhasil ditambahkan ke sesi {$session->name}.");
    }

    /**
     * Hapus absensi dari sesi (rollback).
     */
    public function removeAttendance(Session $session, Attendance $attendance)
    {
        if ($attendance->session_id !== $session->id) {
            abort(403);
        }

        $name = $attendance->participant->name ?? 'Peserta';
        $attendance->delete();

        return back()->with('success', "Absensi {$name} berhasil dihapus dari sesi {$session->name}.");
    }

    public function deactivate(Session $session)
    {
        $session->update(['is_active' => false]);
        return back()->with('success', "Sesi '{$session->name}' dinonaktifkan.");
    }

    public function sendReport(Request $request, Session $session)
    {
        $validated = $request->validate([
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        $groups = \App\Models\Group::whereIn('id', $validated['group_ids'])->get();

        foreach ($groups as $group) {
            \App\Jobs\SendWhatsAppReport::dispatch($group, $session, true);
        }

        return back()->with('success', "Laporan absensi sesi '{$session->name}' berhasil dijadwalkan untuk dikirim ke " . $groups->count() . " kelompok pembina.");
    }
}
