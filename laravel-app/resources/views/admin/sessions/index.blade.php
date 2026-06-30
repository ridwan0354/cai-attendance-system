@extends('layouts.app')
@section('title', 'Kelola Sesi')

@section('content')
<div style="padding:1.25rem;max-width:900px;margin:0 auto;">
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <h1 style="font-size:1.25rem;font-weight:800;">📅 Kelola Sesi Event</h1>
        <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">+ Tambah Sesi</a>
    </div>

    @if(session('success'))
        <div style="background:var(--success-lt);border:1px solid var(--success);color:var(--success);padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:var(--danger-lt);border:1px solid var(--danger);color:var(--danger);padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body" style="padding:0;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Sesi</th>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Hari</th>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Tanggal</th>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Waktu</th>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Status</th>
                        <th style="padding:.65rem 1rem;text-align:left;font-size:.75rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $s)
                    <tr style="{{ $s->is_active ? 'background: var(--success-lt);' : '' }}">
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);font-weight:600;">{{ $s->name }}</td>
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">
                            <span class="badge badge-primary">Hari {{ $s->day_number }}</span>
                        </td>
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">{{ $s->date->format('d M Y') }}</td>
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">{{ $s->start_time }} – {{ $s->end_time }}</td>
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">
                            @if($s->is_active)
                                <span class="badge badge-success">● Aktif</span>
                            @else
                                <span class="badge" style="background:var(--neutral-100);color:var(--neutral-500);">Tidak Aktif</span>
                            @endif
                        </td>
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100); display: flex; gap: 0.25rem; align-items: center;">
                            @if(!$s->is_active)
                                <form action="{{ route('admin.sessions.activate', $s) }}" method="POST" style="display:inline; margin: 0;">
                                    @csrf
                                    <button class="btn btn-success btn-sm">▶ Aktifkan</button>
                                </form>
                            @else
                                <form action="{{ route('admin.sessions.deactivate', $s) }}" method="POST" style="display:inline; margin: 0;">
                                    @csrf
                                    <button class="btn btn-outline btn-sm">⏹ Nonaktifkan</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.sessions.edit', $s) }}" class="btn btn-outline btn-sm">Edit</a>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openReportModal({{ $s->id }}, '{{ addslashes($s->name) }}')" style="border: 1px solid var(--primary); color: var(--primary); background: transparent;">✉️ Kirim Laporan</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
<!-- Modal for Manual Report -->
<div id="reportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg); overflow: hidden; animation: modalFadeIn 0.25s ease; border: 1px solid var(--neutral-200);">
        <div style="background: var(--neutral-50); padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--neutral-200); display: flex; align-items: center; justify-content: space-between;">
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--neutral-800);">✉️ Kirim Laporan Absensi Manual</h3>
            <button type="button" onclick="closeReportModal()" style="background: transparent; border: none; font-size: 1.25rem; cursor: pointer; color: var(--neutral-400); font-weight: 700; line-height: 1;">&times;</button>
        </div>
        
        <form id="reportForm" action="" method="POST" style="margin: 0;">
            @csrf
            <div style="padding: 1.5rem; max-height: 350px; overflow-y: auto;">
                <p style="margin-top: 0; font-size: 0.85rem; color: var(--neutral-600); line-height: 1.5; margin-bottom: 1rem;">
                    Pilih kelompok regional di bawah ini untuk mengirimkan laporan kehadiran sesi <strong id="modalSessionName"></strong> ke nomor WhatsApp Pembina Kelompok masing-masing.
                </p>
                
                <div style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1.5px solid var(--neutral-200); display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="checkAllGroups" onchange="toggleSelectAllGroups(this)" style="cursor: pointer; width: 16px; height: 16px;">
                    <label for="checkAllGroups" style="font-weight: 700; font-size: 0.875rem; color: var(--neutral-800); cursor: pointer; user-select: none;">Pilih Semua Kelompok</label>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                    @forelse($groups as $g)
                        <div style="display: flex; align-items: flex-start; gap: 10px; padding: 0.25rem 0;">
                            <input type="checkbox" name="group_ids[]" value="{{ $g->id }}" class="group-checkbox" id="g-check-{{ $g->id }}" style="cursor: pointer; width: 16px; height: 16px; margin-top: 2px;">
                            <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                <label for="g-check-{{ $g->id }}" style="font-weight: 600; font-size: 0.85rem; color: var(--neutral-800); cursor: pointer; user-select: none;">
                                    {{ $g->name }}
                                </label>
                                <span style="font-size: 0.72rem; color: var(--neutral-500);">Pembina: {{ $g->pembina_name }} ({{ $g->pembina_phone }})</span>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; color: var(--neutral-400); font-size: 0.85rem; padding: 1.5rem 0;">
                            Belum ada kelompok terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>
            
            <div style="background: var(--neutral-50); padding: 1rem 1.5rem; border-top: 1px solid var(--neutral-200); display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeReportModal()" class="btn btn-outline" style="padding: 0.5rem 1.25rem; font-size: 0.85rem; border: 1px solid var(--neutral-300); color: var(--neutral-600); background: white; border-radius: 6px; cursor: pointer;">Batal</button>
                <button type="submit" class="btn" style="background: var(--primary); color: white; border: none; padding: 0.5rem 1.5rem; font-size: 0.85rem; font-weight: 700; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,82,204,0.15);">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
</style>

<script>
    function openReportModal(sessionId, sessionName) {
        const modal = document.getElementById('reportModal');
        const form = document.getElementById('reportForm');
        const sessionNameEl = document.getElementById('modalSessionName');
        
        form.action = `/admin/sessions/${sessionId}/send-report`;
        sessionNameEl.textContent = sessionName;
        
        // Reset inputs
        document.getElementById('checkAllGroups').checked = false;
        document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = false);
        
        modal.style.display = 'flex';
    }
    
    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
    }
    
    function toggleSelectAllGroups(source) {
        document.querySelectorAll('.group-checkbox').forEach(cb => cb.checked = source.checked);
    }
    
    // Close modal on click outside content
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('reportModal');
        if (e.target === modal) {
            closeReportModal();
        }
    });
</script>
@endsection
