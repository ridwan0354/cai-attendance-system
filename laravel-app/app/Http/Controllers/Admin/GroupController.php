<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::withCount('participants')->with('latestNotificationLog')->get();
        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        if (!$request->has('region_code') || empty($request->input('region_code'))) {
            $name = $request->input('name', '');
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
            if (empty($code)) {
                $code = 'GRP' . rand(100, 999);
            }
            $code = substr($code, 0, 15);
            
            $originalCode = $code;
            $suffix = 1;
            while (\App\Models\Group::where('region_code', $code)->exists()) {
                $code = substr($originalCode, 0, 15 - strlen((string)$suffix)) . $suffix;
                $suffix++;
            }
            
            $request->merge(['region_code' => $code]);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'region_code'   => 'required|string|max:20|unique:groups',
            'pembina_name'  => 'required|string|max:255',
            'pembina_phone' => 'required|string|max:20',
            'color'         => 'required|string|max:7',
        ]);

        $group = Group::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'group'   => $group
            ]);
        }

        return redirect()->route('admin.groups.index')->with('success', 'Kelompok berhasil dibuat.');
    }

    public function edit(Group $group)
    {
        return view('admin.groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'pembina_name'  => 'required|string|max:255',
            'pembina_phone' => 'required|string|max:20',
            'color'         => 'required|string|max:7',
        ]);

        $group->update($validated);
        return redirect()->route('admin.groups.index')->with('success', 'Kelompok diperbarui.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Kelompok dihapus.');
    }

    public function sendRecap(Group $group, \App\Services\FonnteWhatsAppService $waService)
    {
        $success = $waService->sendAllSessionsRecapReport($group);
        if ($success) {
            return back()->with('success', "Rekap laporan WA untuk kelompok '{$group->name}' berhasil terkirim ke Pembina ({$group->pembina_phone}).");
        }

        $latestLog = $group->latestNotificationLog;
        $errorMsg = $latestLog?->error_message ?? 'Koneksi Fonnte bermasalah / nomor tidak aktif';

        return back()->with('warning', "Gagal mengirim Rekap WA ke Pembina '{$group->name}': {$errorMsg}");
    }

    public function sendAllSessionsReport(Request $request, \App\Services\FonnteWhatsAppService $waService)
    {
        $validated = $request->validate([
            'group_ids'   => 'required|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        $groups = Group::whereIn('id', $validated['group_ids'])->get();
        $successCount = 0;
        $failCount = 0;

        foreach ($groups as $index => $group) {
            $ok = $waService->sendAllSessionsRecapReport($group);
            if ($ok) {
                $successCount++;
            } else {
                $failCount++;
            }

            if ($index < count($groups) - 1) {
                sleep(2);
            }
        }

        if ($failCount === 0) {
            return back()->with('success', "Rekap laporan semua sesi kegiatan berhasil terkirim ke {$successCount} kelompok pembina!");
        }

        return back()->with('warning', "Laporan terkirim ke {$successCount} kelompok, namun gagal ke {$failCount} kelompok. Cek status di kartu kelompok.");
    }
}
