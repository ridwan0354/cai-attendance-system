@extends('layouts.app')
@section('title', 'Kelola Kelompok')

@push('styles')
<style>
    .admin-layout { padding: 1.25rem; max-width: 1000px; margin: 0 auto; }
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
    .page-title { font-size: 1.25rem; font-weight: 800; color: var(--neutral-900); }
    
    .groups-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    .group-card {
        background: white;
        border-radius: 8px;
        padding: 1.15rem;
        border: 1px solid var(--neutral-200);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .alert-success { background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }
    .alert-warning { background: var(--warning-lt); border: 1px solid var(--warning); color: #7a4f00; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }
</style>
@endpush

@section('content')
<div class="admin-layout">
    <!-- Navbar Admin Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.attendances.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📋 Log Absensi</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">🗺️ Kelompok Regional</h1>
            <p style="font-size: 0.82rem; color: var(--neutral-500); margin-top: 2px;">Kelola kelompok, pembina, dan kirim rekap kehadiran semua sesi</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="openRecapModal()" style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; border-color: var(--primary); color: var(--primary);">
                📲 Kirim Rekap Semua Sesi (WA)
            </button>
            <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">+ Tambah Kelompok</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert-warning">⚠️ {{ session('warning') }}</div>
    @endif

    <div class="groups-grid">
        @foreach($groups as $g)
        <div class="group-card" style="border-top: 4px solid {{ $g->color }};">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                    <div style="font-weight: 700; font-size: 1rem; color: var(--neutral-900);">{{ $g->name }}</div>
                    <span class="badge" style="background: {{ $g->color }}; color: #ffffff; font-size: 0.7rem;">{{ $g->region_code }}</span>
                </div>
                
                <div style="font-size: 0.78rem; color: var(--neutral-500); margin-bottom: 0.5rem;">
                    👥 {{ $g->participants_count }} {{ stripos($g->name, 'panitia') !== false ? 'panitia' : 'peserta' }}
                </div>

                <div style="font-size: 0.84rem; font-weight: 600; color: var(--neutral-800); margin-top: 0.5rem;">
                    👤 Pembina: {{ $g->pembina_name ?: '-' }}
                </div>

                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $g->pembina_phone ?? '');
                    if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '62' . substr($cleanPhone, 1);
                    }
                @endphp
                
                <div style="font-size: 0.78rem; color: var(--neutral-500); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                    <span>📱 {{ $g->pembina_phone ?: '-' }}</span>
                    @if($cleanPhone)
                        <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" style="text-decoration:none; font-size: 0.68rem; color: var(--primary); font-weight: 700;">💬 wa.me</a>
                    @endif
                </div>

                <!-- Status WA Terakhir -->
                <div style="margin-top: 0.6rem;">
                    @if($g->latestNotificationLog)
                        @if($g->latestNotificationLog->status === 'sent')
                            <div style="font-size: 0.72rem; color: #00875a; background: #e6f4ea; border: 1px solid #b7e1cd; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                                ✅ WA Terkirim ({{ $g->latestNotificationLog->sent_at?->setTimezone('Asia/Makassar')->format('d/m H:i') ?? $g->latestNotificationLog->created_at->setTimezone('Asia/Makassar')->format('d/m H:i') }} WITA)
                            </div>
                        @elseif($g->latestNotificationLog->status === 'pending')
                            <div style="font-size: 0.72rem; color: #b76e00; background: #fef7e0; border: 1px solid #fce8b2; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;">
                                ⏳ WA Antrean (Proses Pengiriman)
                            </div>
                        @else
                            <div style="font-size: 0.72rem; color: #d93025; background: #fce8e6; border: 1px solid #fad2cf; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;" title="{{ $g->latestNotificationLog->error_message }}">
                                ❌ WA Gagal Terkirim
                            </div>
                        @endif
                    @else
                        <div style="font-size: 0.72rem; color: var(--neutral-400);">
                            💬 Belum ada log laporan WA
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: flex; gap: 0.4rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--neutral-150); flex-wrap: wrap; align-items: center;">
                <form action="{{ route('admin.groups.send-recap', $g) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Kirim rekap kehadiran semua sesi via WhatsApp ke Pembina {{ addslashes($g->pembina_name) }}?')">
                    @csrf
                    <button type="submit" class="btn btn-sm" style="background: var(--success-lt); color: var(--success); border: 1px solid var(--success); font-weight: 700; padding: 0.35rem 0.6rem;" title="Kirim Rekap Kehadiran Semua Sesi ke Pembina">
                        📲 Rekap WA
                    </button>
                </form>

                <a href="{{ route('admin.groups.edit', $g) }}" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.65rem;">Edit</a>

                <form action="{{ route('admin.groups.destroy', $g) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus kelompok ini? Semua peserta di dalamnya akan ikut terhapus!')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.35rem 0.65rem;">Hapus</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Kirim Rekap WA Semua Sesi -->
<div id="recapModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 520px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200);">
        <h3 style="margin-bottom: 0.75rem; font-weight: 800; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900); border-bottom: 1px solid var(--neutral-150); padding-bottom: 0.5rem;">
            <span>📲 Kirim Rekap Kehadiran Semua Sesi</span>
            <span onclick="closeRecapModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>

        <p style="font-size: 0.83rem; color: var(--neutral-600); margin-bottom: 1rem;">
            Laporan ini berisi rekap total kehadiran peserta di <strong>semua sesi kegiatan</strong> (jumlah sesi yang diikuti & sesi yang tidak diikuti), dan dikirim langsung ke nomor WhatsApp Pembina Kelompok.
        </p>

        <form action="{{ route('admin.groups.send-all-sessions-report') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <label style="font-size: 0.84rem; font-weight: 700; color: var(--neutral-800);">Pilih Kelompok Pembina *</label>
                    <label style="font-size: 0.78rem; color: var(--primary); font-weight: 700; cursor: pointer; user-select: none;">
                        <input type="checkbox" id="selectAllGroups" onchange="toggleSelectAllGroups(this)" style="cursor: pointer;"> Pilih Semua
                    </label>
                </div>

                <div style="max-height: 220px; overflow-y: auto; background: var(--neutral-50); border: 1.5px solid var(--neutral-200); border-radius: 6px; padding: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach($groups as $g)
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; cursor: pointer; user-select: none; padding: 3px 0;">
                            <input type="checkbox" name="group_ids[]" value="{{ $g->id }}" class="group-checkbox" style="cursor: pointer;" checked>
                            <span class="badge" style="background: {{ $g->color }}; color: #fff; font-size: 0.68rem; padding: 2px 6px;">{{ $g->name }}</span>
                            <span style="color: var(--neutral-700); font-weight: 600;">{{ $g->pembina_name ?: '-' }}</span>
                            <span style="font-size: 0.75rem; color: var(--neutral-500);">({{ $g->pembina_phone }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--neutral-150); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeRecapModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="font-weight: 700;">📲 Kirim WhatsApp Rekap</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRecapModal() {
    document.getElementById('recapModal').style.display = 'flex';
}
function closeRecapModal() {
    document.getElementById('recapModal').style.display = 'none';
}
function toggleSelectAllGroups(source) {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>
@endsection
