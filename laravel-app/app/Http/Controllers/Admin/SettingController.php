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

        $waGateway = Setting::getVal('wa_gateway', 'fonnte');
        $fonnteApiKey = Setting::getVal('fonnte_api_key', '');
        $grooviteApiUrl = Setting::getVal('groovite_api_url', 'https://waa.galipatsistem.com/api');
        $grooviteWaKey = Setting::getVal('groovite_wa_key', '');
        $notifyPembinaEnabled = Setting::getVal('notify_pembina_enabled', '1');
        $notifyPesertaEnabled = Setting::getVal('notify_peserta_enabled', '1');
        $pesertaMessageTemplate = Setting::getVal('peserta_message_template', Setting::defaultPesertaTemplate());
        $pembinaMessageTemplate = Setting::getVal('pembina_message_template', Setting::defaultPembinaTemplate());

        return view('admin.settings.index', compact(
            'waGateway', 'fonnteApiKey', 'grooviteApiUrl', 'grooviteWaKey',
            'notifyPembinaEnabled', 'notifyPesertaEnabled',
            'pesertaMessageTemplate', 'pembinaMessageTemplate'
        ));
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
            'wa_gateway' => 'required|string|in:fonnte,groovite',
            'fonnte_api_key' => 'nullable|string|max:255',
            'groovite_api_url' => 'nullable|string|max:255',
            'groovite_wa_key' => 'nullable|string|max:255',
            'notify_pembina_enabled' => 'nullable|string|in:0,1',
            'notify_peserta_enabled' => 'nullable|string|in:0,1',
            'peserta_message_template' => 'nullable|string',
            'pembina_message_template' => 'nullable|string',
        ]);

        Setting::setVal('wa_gateway', $validated['wa_gateway']);
        Setting::setVal('fonnte_api_key', $validated['fonnte_api_key'] ?? '');
        Setting::setVal('groovite_api_url', $validated['groovite_api_url'] ?? 'https://waa.galipatsistem.com/api');
        Setting::setVal('groovite_wa_key', $validated['groovite_wa_key'] ?? '');
        Setting::setVal('notify_pembina_enabled', $request->input('notify_pembina_enabled', '0'));
        Setting::setVal('notify_peserta_enabled', $request->input('notify_peserta_enabled', '0'));
        Setting::setVal('peserta_message_template', $request->input('peserta_message_template') ?? Setting::defaultPesertaTemplate());
        Setting::setVal('pembina_message_template', $request->input('pembina_message_template') ?? Setting::defaultPembinaTemplate());

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

    /**
     * Send a test WhatsApp message.
     */
    public function testWA(Request $request, \App\Services\FonnteWhatsAppService $waService)
    {
        if (!session('settings_unlocked')) {
            return redirect()->route('admin.settings.index');
        }

        $validated = $request->validate([
            'test_phone' => 'required|string',
        ]);

        $gateway = Setting::getVal('wa_gateway', 'fonnte');
        if ($gateway === 'groovite') {
            $apiKey = Setting::getVal('groovite_wa_key', '');
            if (empty($apiKey)) {
                return back()->with('error', 'Token Groovite belum disimpan. Silakan simpan token terlebih dahulu.');
            }
        } else {
            $apiKey = Setting::getVal('fonnte_api_key', '');
            if (empty($apiKey)) {
                return back()->with('error', 'Token Fonnte belum disimpan. Silakan simpan token terlebih dahulu.');
            }
        }

        $message = "✅ *Tes Koneksi CAI LOMBOK 2026*\n\n";
        $message .= "Halo! Ini adalah pesan tes dari Aplikasi Absensi Face Recognition CAI Lombok 2026.\n\n";
        $message .= "Jika Anda menerima pesan ini, artinya token API " . ($gateway === 'groovite' ? 'Groovite' : 'Fonnte') . " Anda sudah berhasil terhubung dengan benar! 🚀";

        $res = $waService->sendGenericMessage($validated['test_phone'], $message);

        if ($res['success']) {
            return back()->with('success', "Berhasil mengirim pesan tes ke nomor {$validated['test_phone']}!");
        }

        return back()->with('error', "Gagal mengirim pesan tes. Error: {$res['message']}");
    }
}
