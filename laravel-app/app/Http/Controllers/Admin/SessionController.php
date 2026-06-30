<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;

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
        $session->delete();
        return redirect()->route('admin.sessions.index')->with('success', 'Sesi dihapus.');
    }

    public function activate(Session $session)
    {
        $today = now()->format('Y-m-d');
        $sessionDate = $session->date->format('Y-m-d');

        if ($sessionDate !== $today) {
            return back()->with('error', "Gagal mengaktifkan sesi. Tanggal sesi ({$session->date->format('d M Y')}) tidak sesuai dengan tanggal hari ini.");
        }

        $startTime = \Carbon\Carbon::parse($sessionDate . ' ' . $session->start_time);
        $endTime = \Carbon\Carbon::parse($sessionDate . ' ' . $session->end_time);
        $earliestStart = $startTime->copy()->subHour();

        if (now()->lt($earliestStart)) {
            return back()->with('error', "Gagal mengaktifkan sesi. Sesi ini baru dapat diaktifkan paling cepat 1 jam sebelum jadwal dimulai (mulai pukul {$earliestStart->format('H:i')}).");
        }

        if (now()->gt($endTime)) {
            return back()->with('error', "Gagal mengaktifkan sesi. Sesi ini sudah berakhir pada pukul {$endTime->format('H:i')}.");
        }

        // Deactivate all first
        Session::where('is_active', true)->update(['is_active' => false]);
        $session->update(['is_active' => true]);
        return back()->with('success', "Sesi '{$session->name}' diaktifkan.");
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
