<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::withCount('participants')->get();
        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
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

        return redirect()->route('admin.groups.index')->with('success', 'Grup berhasil dibuat.');
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
        return redirect()->route('admin.groups.index')->with('success', 'Grup diperbarui.');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Grup dihapus.');
    }
}
