@extends('layouts.app')
@section('title', 'Kunci Pengaturan')
@section('content')
<div style="padding: 2rem 1.25rem; max-width: 500px; margin: 5vh auto 0;">
    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 2.5rem; padding-bottom: 0.25rem; flex-wrap: wrap; justify-content: center;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-md); border-radius: 12px; overflow: hidden; background: white;">
        <div class="card-header" style="background: var(--neutral-50); padding: 1.25rem; border-bottom: 1px solid var(--neutral-200); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔒</div>
            <span class="card-title" style="font-size: 1.15rem; font-weight: 800; display: block; color: var(--neutral-800);">Kunci Pengaturan</span>
            <span style="font-size: 0.8rem; color: var(--neutral-500); margin-top: 4px; display: block;">Masukkan password untuk mengakses halaman pengaturan API Fonnte.</span>
        </div>
        <div class="card-body" style="padding: 1.5rem 2rem;">
            @if($errors->has('password'))
                <div style="background: var(--danger-lt); border: 1px solid var(--danger); color: var(--danger); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .85rem; font-weight: 600; text-align: center;">
                    ⚠️ {{ $errors->first('password') }}
                </div>
            @endif

            <form action="{{ route('admin.settings.unlock') }}" method="POST">
                @csrf
                <div style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Password Akses</label>
                    <input type="password" name="password" id="password" required autofocus placeholder="Masukkan password..." 
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--neutral-300); border-radius: 8px; font-size: 0.95rem; text-align: center; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); transition: border-color 0.15s; outline: none;"
                           onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                </div>

                <button type="submit" class="btn" style="width: 100%; background: var(--primary); color: white; border: none; padding: 0.8rem; font-size: 0.95rem; font-weight: 700; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0, 82, 204, 0.2);">
                    Buka Pengaturan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
