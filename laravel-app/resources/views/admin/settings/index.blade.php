@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div style="padding: 1.25rem; max-width: 700px; margin: 0 auto;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <h1 style="font-size: 1.25rem; font-weight: 800;">⚙️ Pengaturan Sistem</h1>
        <form action="{{ route('admin.settings.lock') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; display: flex; align-items: center; gap: 4px; border: 1px solid var(--neutral-300); color: var(--neutral-600); border-radius: 6px; background: white; cursor: pointer;">
                🔒 Kunci Akses
            </button>
        </form>
    </div>

    @if(session('success'))
        <div style="background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 8px; overflow: hidden; background: white;">
        <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
            <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">Konfigurasi Gateway WhatsApp (Fonnte)</span>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <form action="{{ route('admin.settings.store') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label for="fonnte_api_key" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Token API Fonnte</label>
                    <input type="text" name="fonnte_api_key" id="fonnte_api_key" value="{{ $fonnteApiKey }}" placeholder="Masukkan token Fonnte..." 
                           style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                           onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                    <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                        Token API ini digunakan untuk mengirim pesan verifikasi kehadiran serta laporan kehadiran berkala kepada para Pembina Kelompok secara otomatis.
                    </span>
                </div>

                <div style="border-top: 1px solid var(--neutral-200); padding-top: 1rem; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn" style="background: var(--primary); color: white; border: none; padding: 0.6rem 1.5rem; font-size: 0.9rem; font-weight: 700; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0, 82, 204, 0.15);">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
