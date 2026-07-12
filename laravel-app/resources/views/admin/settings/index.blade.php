@extends('layouts.app')
@section('title', 'Pengaturan')
@section('content')
<div style="padding: 1.25rem; max-width: 1000px; margin: 0 auto;">
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
        <div style="background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: var(--danger-lt); border: 1px solid var(--danger); color: var(--danger); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .875rem;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Left: Configuration Form -->
        <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 8px; overflow: hidden; background: white;">
            <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
                <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">Konfigurasi Gateway WhatsApp</span>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <form action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    
                    <!-- Gateway Selection -->
                    <div style="margin-bottom: 1.5rem;">
                        <label for="wa_gateway" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Pilih Gateway WhatsApp</label>
                        <select name="wa_gateway" id="wa_gateway" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; background: white;" onchange="toggleGatewayFields()">
                            <option value="fonnte" {{ $waGateway === 'fonnte' ? 'selected' : '' }}>Fonnte Gateway</option>
                            <option value="groovite" {{ $waGateway === 'groovite' ? 'selected' : '' }}>Custom Gateway (Groovite / Galipat)</option>
                        </select>
                    </div>

                    <!-- Fonnte Fields -->
                    <div id="fonnte_fields" style="margin-bottom: 1.5rem; display: {{ $waGateway === 'fonnte' ? 'block' : 'none' }};">
                        <label for="fonnte_api_key" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Token API Fonnte</label>
                        <input type="text" name="fonnte_api_key" id="fonnte_api_key" value="{{ $fonnteApiKey }}" placeholder="Masukkan token Fonnte..." 
                               style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                               onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                            Token API Fonnte digunakan untuk mengirim pesan via Fonnte.
                        </span>
                    </div>

                    <!-- Groovite Fields -->
                    <div id="groovite_fields" style="margin-bottom: 1.5rem; display: {{ $waGateway === 'groovite' ? 'block' : 'none' }};">
                        <div style="margin-bottom: 1rem;">
                            <label for="groovite_api_url" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Base API URL</label>
                            <input type="text" name="groovite_api_url" id="groovite_api_url" value="{{ $grooviteApiUrl }}" placeholder="Contoh: https://waa.galipatsistem.com/api" 
                                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        </div>
                        <div>
                            <label for="groovite_wa_key" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">API Key (waKey)</label>
                            <input type="text" name="groovite_wa_key" id="groovite_wa_key" value="{{ $grooviteWaKey }}" placeholder="Masukkan waKey..." 
                                   style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                                   onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        </div>
                        <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                            API Key (waKey) dan Base URL ini digunakan untuk mengirim pesan via Custom Gateway.
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

        <!-- Right: Test Connection Form -->
        <div class="card" style="border: 1px solid var(--neutral-200); box-shadow: var(--shadow-sm); border-radius: 8px; overflow: hidden; background: white;">
            <div class="card-header" style="background: var(--neutral-50); padding: 1rem 1.25rem; border-bottom: 1px solid var(--neutral-200);">
                <span class="card-title" style="font-size: 0.95rem; font-weight: 800; color: var(--neutral-800);">Tes Koneksi Gateway WhatsApp</span>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <form action="{{ route('admin.settings.test-wa') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 1.5rem;">
                        <label for="test_phone" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--neutral-700); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Nomor WhatsApp Penerima</label>
                        <input type="text" name="test_phone" id="test_phone" required placeholder="Contoh: 081234567890..." 
                               style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--neutral-300); border-radius: 6px; font-size: 0.9rem; outline: none; transition: border-color 0.15s;"
                               onfocus="this.style.borderColor='var(--primary)';" onblur="this.style.borderColor='var(--neutral-300)';">
                        <span style="font-size: 0.75rem; color: var(--neutral-500); display: block; margin-top: 6px; line-height: 1.4;">
                            Masukkan nomor WhatsApp tujuan (misalnya nomor Anda sendiri) untuk menguji apakah gateway WhatsApp yang disimpan sudah berfungsi dengan benar.
                        </span>
                    </div>

                    <div style="border-top: 1px solid var(--neutral-200); padding-top: 1rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary); background: transparent; padding: 0.6rem 1.5rem; font-size: 0.9rem; font-weight: 700; border-radius: 6px; cursor: pointer; transition: all 0.15s;"
                                 onmouseover="this.style.background='var(--primary-lt)';" onmouseout="this.style.background='transparent';">
                            🚀 Kirim Pesan Tes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleGatewayFields() {
        const gateway = document.getElementById('wa_gateway').value;
        const fonnteFields = document.getElementById('fonnte_fields');
        const grooviteFields = document.getElementById('groovite_fields');
        
        if (gateway === 'groovite') {
            fonnteFields.style.display = 'none';
            grooviteFields.style.display = 'block';
        } else {
            fonnteFields.style.display = 'block';
            grooviteFields.style.display = 'none';
        }
    }
</script>
@endsection
