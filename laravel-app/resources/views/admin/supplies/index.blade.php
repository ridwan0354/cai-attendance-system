@extends('layouts.app')
@section('title', 'Barang Registrasi')
@section('content')
<div style="padding:1.25rem;max-width:900px;margin:0 auto;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">🎁 Barang Registrasi</a>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <h1 style="font-size:1.25rem;font-weight:800;">🎁 Pengaturan Barang Registrasi</h1>
    </div>

    @if(session('success'))
        <div style="background:var(--success-lt);border:1px solid var(--success);color:var(--success);padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- List of items -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Daftar Barang</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom: 1.5px solid var(--neutral-200); text-align: left; background: var(--neutral-50);">
                            <th style="padding: 0.75rem 1rem; font-size: 0.82rem; font-weight: 600; color: var(--neutral-600);">Nama Barang</th>
                            <th style="padding: 0.75rem 1rem; font-size: 0.82rem; font-weight: 600; color: var(--neutral-600); width: 80px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplies as $item)
                        <tr style="border-bottom: 1px solid var(--neutral-150);">
                            <td style="padding: 0.85rem 1rem; font-size: 0.875rem; font-weight: 500; color: var(--neutral-800);">
                                {{ $item->name }}
                            </td>
                            <td style="padding: 0.5rem 1rem; text-align: center;">
                                <form action="{{ route('admin.supplies.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus barang ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 0.75rem;">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="padding: 2rem; text-align: center; color: var(--neutral-400); font-size: 0.875rem;">
                                Belum ada barang registrasi yang dikonfigurasi.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add new item -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">+ Tambah Barang Baru</span>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.supplies.store') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Nama Barang *</label>
                        <input type="text" name="name" required placeholder="Contoh: Kaos, Kitab, ID Card, Konsumsi" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;outline:none;">
                        @error('name')
                            <p style="color:var(--danger);font-size:0.75rem;margin-top:0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Simpan Barang</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
