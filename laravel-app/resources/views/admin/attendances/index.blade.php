@extends('layouts.app')
@section('title', 'Log Keabsenan Peserta')

@push('styles')
<style>
    .admin-layout { padding: 1.25rem; max-width: 1200px; margin: 0 auto; }
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
    .page-title { font-size: 1.25rem; font-weight: 800; color: var(--neutral-900); }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        border: 1px solid var(--neutral-200);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .stat-label { font-size: 0.75rem; font-weight: 600; color: var(--neutral-500); text-transform: uppercase; letter-spacing: .05em; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: var(--neutral-900); margin-top: 4px; }
    
    table { width: 100%; border-collapse: collapse; }
    th { padding: .65rem 1rem; text-align: left; font-size: .75rem; font-weight: 600; color: var(--neutral-500); text-transform: uppercase; letter-spacing: .05em; border-bottom: 2px solid var(--neutral-200); }
    td { padding: .75rem 1rem; font-size: .875rem; border-bottom: 1px solid var(--neutral-100); vertical-align: middle; }
    tr:hover td { background: var(--neutral-50); }

    .alert-success { background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }
    .alert-warning { background: var(--warning-lt); border: 1px solid var(--warning); color: #7a4f00; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }

    .method-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .method-face { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
    .method-qr { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .method-rfid { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
    .method-manual { background: #f3e5f5; color: #7b1fa2; border: 1px solid #e1bee7; }
</style>
@endpush

@section('content')
<div class="admin-layout">
    <!-- Navbar Admin Tabs -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.attendances.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">📋 Log Absensi</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">⚙️ Pengaturan</a>
    </div>

    <!-- Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">📋 Log Keabsenan Peserta</h1>
            <p style="font-size: 0.84rem; color: var(--neutral-500); margin-top: 2px;">Riwayat kehadiran peserta di setiap sesi kegiatan</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openManualModal()">+ Absensi Manual</button>
    </div>

    @if(session('success'))
        <div class="alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert-warning">⚠️ {{ session('warning') }}</div>
    @endif

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Hadir</div>
            <div class="stat-value" style="color: var(--primary);">{{ number_format($totalAttendances) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Deteksi Wajah</div>
            <div class="stat-value" style="color: #1565c0;">{{ number_format($faceCount) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Scan QR Code</div>
            <div class="stat-value" style="color: #2e7d32;">{{ number_format($qrCount) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Input Manual</div>
            <div class="stat-value" style="color: #7b1fa2;">{{ number_format($manualCount) }}</div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card" style="margin-bottom: 1.25rem; padding: 1rem; background: white;">
        <form action="{{ route('admin.attendances.index') }}" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Cari nama peserta..." value="{{ request('search') }}" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; font-family: inherit;">
            </div>
            
            <div style="width: 180px; min-width: 140px;">
                <select name="session_id" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; background: white; font-family: inherit;">
                    <option value="">— Semua Sesi —</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>
                            Hari {{ $s->day_number }} - {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 180px; min-width: 140px;">
                <select name="group_id" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; background: white; font-family: inherit;">
                    <option value="">— Semua Kelompok —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 160px; min-width: 130px;">
                <select name="method" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; background: white; font-family: inherit;">
                    <option value="">— Metode —</option>
                    <option value="face" {{ request('method') == 'face' ? 'selected' : '' }}>📸 Wajah</option>
                    <option value="qr" {{ request('method') == 'qr' ? 'selected' : '' }}>📱 QR Code</option>
                    <option value="rfid" {{ request('method') == 'rfid' ? 'selected' : '' }}>💳 RFID</option>
                    <option value="manual" {{ request('method') == 'manual' ? 'selected' : '' }}>✏️ Manual</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">🔍 Filter</button>
                @if(request()->filled('search') || request()->filled('session_id') || request()->filled('group_id') || request()->filled('method'))
                    <a href="{{ route('admin.attendances.index') }}" class="btn btn-outline" style="padding: 0.5rem 1.25rem; text-decoration: none; display: inline-flex; align-items: center;">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Waktu Check-in</th>
                        <th>Nama Peserta</th>
                        <th>Kelompok</th>
                        <th>Sesi</th>
                        <th>Metode</th>
                        <th>Catatan</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $a)
                    @php
                        $score = $a->confidence_score;
                        $scoreText = null;
                        if ($score !== null) {
                            if ($score > 1 && $score <= 100) {
                                $scoreText = round($score) . '%';
                            } elseif ($score >= 0 && $score <= 1) {
                                $scoreText = max(0, round((1 - $score) * 100)) . '%';
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $a->id }}</td>
                        <td>
                            <div style="font-weight: 600; color: var(--neutral-900);">
                                {{ $a->check_in_time->format('d/m/Y H:i:s') }}
                            </div>
                            <div style="font-size: 0.72rem; color: var(--neutral-500); margin-top: 2px;">
                                🕒 {{ $a->check_in_time->diffForHumans() }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $a->participant->name ?? '-' }}</div>
                            @if(!empty($a->participant->phone))
                                <div style="font-size: 0.72rem; color: var(--neutral-500);">📱 {{ $a->participant->phone }}</div>
                            @endif
                        </td>
                        <td>
                            @if($a->participant && $a->participant->group)
                                <span class="badge" style="background: {{ $a->participant->group->color }}; color: #ffffff; text-shadow: 0 1px 2px rgba(0,0,0,0.25);">
                                    {{ $a->participant->group->name }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.85rem;">{{ $a->session->name ?? '-' }}</div>
                            <div style="font-size: 0.72rem; color: var(--neutral-500);">Hari ke-{{ $a->session->day_number ?? 1 }}</div>
                        </td>
                        <td>
                            @if($a->method === 'face')
                                <span class="method-badge method-face">
                                    📸 Face
                                    @if($scoreText)
                                        ({{ $scoreText }})
                                    @endif
                                </span>
                            @elseif($a->method === 'qr')
                                <span class="method-badge method-qr">📱 QR Code</span>
                            @elseif($a->method === 'rfid')
                                <span class="method-badge method-rfid">💳 RFID</span>
                            @else
                                <span class="method-badge method-manual">✏️ Manual</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size: 0.8rem; color: var(--neutral-600);">{{ $a->notes ?: '-' }}</span>
                        </td>
                        <td style="text-align: right; display: flex; gap: 0.35rem; justify-content: flex-end; align-items: center;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="openMoveSessionModal({{ $a->id }}, '{{ addslashes($a->participant->name ?? 'Peserta') }}', {{ $a->session_id }}, '{{ addslashes($a->notes ?? '') }}')" style="padding: 0.35rem 0.65rem;" title="Pindahkan ke sesi lain">
                                🔄 Pindah Sesi
                            </button>
                            <form action="{{ route('admin.attendances.destroy', $a) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus log absensi ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.35rem 0.65rem;">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2.5rem 1rem; color: var(--neutral-500);">
                            <div>📭 Belum ada log absensi yang sesuai filter.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 1rem;">
        {{ $attendances->links() }}
    </div>
</div>

<!-- Modal Pindah Sesi -->
<div id="moveSessionModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200);">
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900); border-bottom: 1px solid var(--neutral-150); padding-bottom: 0.5rem;">
            <span id="moveModalTitle">🔄 Pindah Sesi Absensi</span>
            <span onclick="closeMoveSessionModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>

        <form id="moveSessionForm" method="POST">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.4rem; color: var(--neutral-700);">Peserta</label>
                <input type="text" id="moveParticipantName" readonly disabled style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;background:var(--neutral-100);color:var(--neutral-700);font-weight:600;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.4rem; color: var(--neutral-700);">Pindah Ke Sesi Tujuan *</label>
                <select name="session_id" id="moveSessionId" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;outline:none;background:white;font-family:inherit;">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}">Hari {{ $s->day_number }} - {{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem; color: var(--neutral-700);">Catatan (Opsional)</label>
                <input type="text" name="notes" id="moveNotes" placeholder="Contoh: Dipindahkan oleh admin" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;outline:none;">
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--neutral-150); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeMoveSessionModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Absensi Manual -->
<div id="manualModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200);">
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900); border-bottom: 1px solid var(--neutral-150); padding-bottom: 0.5rem;">
            <span>✏️ Input Absensi Manual</span>
            <span onclick="closeManualModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>

        <form action="{{ route('admin.attendances.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.4rem; color: var(--neutral-700);">Filter Kelompok (Opsional)</label>
                <select id="manualFilterGroup" onchange="filterManualParticipants(this.value)" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;outline:none;background:white;font-family:inherit;">
                    <option value="">— Semua Kelompok —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.4rem; color: var(--neutral-700);">Pilih Peserta *</label>
                <select name="participant_id" id="manualParticipantSelect" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;outline:none;background:white;font-family:inherit;">
                    <option value="">— Cari / Pilih Peserta —</option>
                    @foreach($participants as $p)
                        <option value="{{ $p->id }}" data-group-id="{{ $p->group_id }}">{{ $p->name }} ({{ $p->group->name ?? 'Tanpa Kelompok' }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.4rem; color: var(--neutral-700);">Pilih Sesi *</label>
                <select name="session_id" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;outline:none;background:white;font-family:inherit;">
                    <option value="">— Pilih Sesi —</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}">Hari {{ $s->day_number }} - {{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem; color: var(--neutral-700);">Catatan</label>
                <input type="text" name="notes" placeholder="Contoh: Absen manual via panitia" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;outline:none;">
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; border-top: 1px solid var(--neutral-150); padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="closeManualModal()">Batal</button>
                <button type="submit" class="btn btn-primary">💾 Simpan Absensi</button>
            </div>
        </form>
    </div>
</div>

<script>
function filterManualParticipants(groupId) {
    const select = document.getElementById('manualParticipantSelect');
    const options = select.querySelectorAll('option');
    select.value = '';
    
    options.forEach(opt => {
        if (!opt.value) return;
        const optGroupId = opt.getAttribute('data-group-id');
        if (!groupId || optGroupId == groupId) {
            opt.style.display = 'block';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });
}

function openManualModal() {
    document.getElementById('manualFilterGroup').value = '';
    filterManualParticipants('');
    document.getElementById('manualModal').style.display = 'flex';
}
function closeManualModal() {
    document.getElementById('manualModal').style.display = 'none';
}

function openMoveSessionModal(id, participantName, currentSessionId, notes) {
    document.getElementById('moveModalTitle').textContent = `🔄 Pindah Sesi: ${participantName}`;
    document.getElementById('moveParticipantName').value = participantName;
    document.getElementById('moveSessionId').value = currentSessionId;
    document.getElementById('moveNotes').value = notes || '';
    document.getElementById('moveSessionForm').action = `/admin/attendances/${id}`;
    document.getElementById('moveSessionModal').style.display = 'flex';
}
function closeMoveSessionModal() {
    document.getElementById('moveSessionModal').style.display = 'none';
}
</script>
@endsection
