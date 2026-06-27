<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
    public function index()
    {
        $supplies = Supply::orderBy('name')->get();
        return view('admin.supplies.index', compact('supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:supplies,name',
        ]);

        Supply::create($validated);

        return redirect()->route('admin.supplies.index')->with('success', 'Barang registrasi berhasil ditambahkan.');
    }

    public function destroy(Supply $supply)
    {
        $supply->delete();
        return redirect()->route('admin.supplies.index')->with('success', 'Barang registrasi berhasil dihapus.');
    }
}
