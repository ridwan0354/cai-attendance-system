<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Show settings or unlock screen.
     */
    public function index()
    {
        if (!session('settings_unlocked')) {
            return view('admin.settings.unlock');
        }

        $fonnteApiKey = Setting::getVal('fonnte_api_key', '');
        return view('admin.settings.index', compact('fonnteApiKey'));
    }

    /**
     * Unlock settings using password.
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if ($request->password === 'Bismillah354') {
            session(['settings_unlocked' => true]);
            return redirect()->route('admin.settings.index')->with('success', 'Akses Pengaturan Terbuka.');
        }

        return back()->withErrors(['password' => 'Password salah!']);
    }

    /**
     * Save settings.
     */
    public function store(Request $request)
    {
        if (!session('settings_unlocked')) {
            return redirect()->route('admin.settings.index');
        }

        $validated = $request->validate([
            'fonnte_api_key' => 'nullable|string|max:255',
        ]);

        Setting::setVal('fonnte_api_key', $validated['fonnte_api_key'] ?? '');

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Lock settings again.
     */
    public function lock()
    {
        session()->forget('settings_unlocked');
        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan telah dikunci kembali.');
    }
}
