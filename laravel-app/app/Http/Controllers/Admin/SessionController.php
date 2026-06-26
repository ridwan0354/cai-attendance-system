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
        return view('admin.sessions.index', compact('sessions'));
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
}
