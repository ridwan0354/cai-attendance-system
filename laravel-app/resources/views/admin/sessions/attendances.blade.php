@extends('layouts.app')
@section('title', 'Kelola Absensi – ' . $session->name)

@section('content')
<div style="padding:1.25rem;max-width:1100px;margin:0 auto;">

    {{-- Tab nav --}}
    <div style="display:flex;gap:.5rem;border-bottom:2px solid var(--neutral-200);margin-bottom:1.5rem;padding-bottom:.25rem;flex-wrap:wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding:.5rem 1rem;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;color:var(--neutral-500);font-size:.875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding:.5rem 1rem;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;color:var(--neutral-500);font-size:.875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding:.5rem 1rem;font-weight:600;text-decoration:none;border-bottom:3px solid var(--primary);color:var(--primary);font-size:.875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding:.5rem 1rem;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;color:var(--neutral-500);font-size:.875rem;">🎁 Barang</a>
        <a href="{{ route('admin.settings.index') }}" style="padding:.5rem 1rem;font-weight:600;text-decoration:none;border-bottom:3px solid transparent;color:var(--neutral-500);font-size:.875rem;">⚙️ Pengaturan</a>
    </div>

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
        <div>
            <div style="font-size:.8rem;color:var(--neutral-500);margin-bottom:.25rem;">
                <a href="{{ route('admin.sessions.index') }}" style="color:var(--primary);text-decoration:none;">← Kembali ke Daftar Sesi</a>
            </div>
            <h1 style="font-size:1.2rem;font-weight:800;margin:0;">📋 Kelola Absensi: {{ $session->name }}</h1>
            <div style="font-size:.8rem;color:var(--neutral-500);margin-top:.25rem;">
                Hari ke-{{ $session->day_number }} &bull;
                {{ \Carbon\Carbon::parse($session->date)->format('d M Y') }} &bull;
                {{ $session->start_time }} – {{ $session->end_time }}
                @if($session->is_active)
                    &bull; <span style="color:var(--success);font-weight:700;">● Aktif</span>
                @endif
            </div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;">
            <div style="background:var(--success-lt);border:1px solid var(--success);color:var(--success);padding:.5rem 1rem;border-radius:8px;font-size:.875rem;font-weight:700;">
                ✅ Hadir: {{ $attendances->count() }}
            </div>
            <div style="background:var(--danger-lt);border:1px solid var(--danger);color:var(--danger);padding:.5rem 1rem;border-radius:8px;font-size:.875rem;font-weight:700;">
                ❌ Belum: {{ $notAttended->count() }}
            </div>
        </div>
    </div>

    {{-- Alerts --}}
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

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start;">

        {{-- Kiri: Daftar Hadir --}}
        <div class="card">
            <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                <span class="card-title">✅ Peserta yang Sudah Hadir ({{ $attendances->count() }})</span>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <input type="text" id="searchPresent" placeholder="Cari peserta..."
                           oninput="filterPresent()"
                           style="padding:.35rem .65rem;border:1px solid var(--neutral-200);border-radius:6px;font-size:.8rem;outline:none;width:180px;">
                    <select id="filterGroup" onchange="filterPresent()"
                            style="padding:.35rem .6rem;border:1px solid var(--neutral-200);border-radius:6px;font-size:.8rem;outline:none;background:white;cursor:pointer;">
                        <option value="">— Semua Kelompok —</option>
                        @foreach($groups as $g)
                            <option value="{{ strtolower($g->name) }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body" style="padding:0;">
                @if($attendances->isEmpty())
                    <div style="padding:3rem;text-align:center;color:var(--neutral-400);font-size:.875rem;">
                        <div style="font-size:2rem;margin-bottom:.5rem;">📭</div>
                        Belum ada peserta yang tercatat hadir di sesi ini.<br>
                        <span style="font-size:.8rem;">Gunakan form di sebelah kanan untuk tambah absensi manual.</span>
                    </div>
                @else
                    <table style="width:100%;border-collapse:collapse;" id="attendanceTable">
                        <thead>
                            <tr>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">No</th>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Peserta</th>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Kelompok</th>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Waktu</th>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Metode</th>
                                <th style="padding:.6rem 1rem;text-align:left;font-size:.72rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;border-bottom:2px solid var(--neutral-200);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceTbody">
                            @foreach($attendances as $i => $att)
                            <tr data-name="{{ strtolower($att->participant->name ?? '') }}"
                                data-group="{{ strtolower($att->participant->group->name ?? '') }}"
                                style="transition:background .15s;">
                                <td style="padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--neutral-100);color:var(--neutral-400);">{{ $i + 1 }}</td>
                                <td style="padding:.65rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">
                                    <span style="font-weight:600;">{{ $att->participant->name ?? '-' }}</span>
                                    <div style="font-size:.7rem;color:var(--neutral-400);">{{ $att->participant->gender ?? '' }}</div>
                                </td>
                                <td style="padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--neutral-100);">
                                    <span style="display:inline-flex;align-items:center;gap:.3rem;">
                                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $att->participant->group->color ?? '#ccc' }};display:inline-block;flex-shrink:0;"></span>
                                        {{ $att->participant->group->name ?? '-' }}
                                    </span>
                                </td>
                                <td style="padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--neutral-100);white-space:nowrap;">
                                    {{ $att->check_in_time->format('H:i:s') }}
                                </td>
                                <td style="padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--neutral-100);">
                                    @php
                                        $methodMap = ['face'=>'🤖 Wajah','qr'=>'📷 QR','manual'=>'✏️ Manual'];
                                    @endphp
                                    <span style="padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700;
                                        background:{{ $att->method==='manual'?'#fff8e1':'#e8f5e9' }};
                                        color:{{ $att->method==='manual'?'#f57f17':'#2e7d32' }};">
                                        {{ $methodMap[$att->method] ?? $att->method }}
                                    </span>
                                </td>
                                <td style="padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--neutral-100);">
                                    <form action="{{ route('admin.sessions.attendances.remove', [$session, $att]) }}"
                                          method="POST" style="display:inline;"
                                          onsubmit="return confirm('Hapus absensi {{ addslashes($att->participant->name ?? '') }} dari sesi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="font-size:.75rem;padding:.25rem .6rem;">
                                            🗑 Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Kanan: Tambah Absensi Manual --}}
        <div style="display:flex;flex-direction:column;gap:1rem;">

            {{-- Form tambah --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">➕ Tambah Absensi Manual</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sessions.attendances.add', $session) }}" method="POST">
                        @csrf
                        <div style="margin-bottom:.75rem;">
                            <label style="font-size:.8rem;font-weight:600;color:var(--neutral-700);display:block;margin-bottom:.35rem;">
                                Pilih Peserta
                            </label>
                            <input type="text" id="searchAdd" placeholder="Ketik nama..." oninput="filterAddList()" autocomplete="off"
                                   style="width:100%;padding:.5rem .75rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.85rem;outline:none;box-sizing:border-box;margin-bottom:.35rem;">
                            <select name="participant_id" id="participantSelect" required
                                    style="width:100%;padding:.5rem .75rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.85rem;outline:none;background:white;cursor:pointer;box-sizing:border-box;">
                                <option value="">— Pilih peserta —</option>
                                @foreach($notAttended as $p)
                                    <option value="{{ $p->id }}"
                                            data-name="{{ strtolower($p->name) }}"
                                            data-group="{{ strtolower($p->group->name ?? '') }}">
                                        {{ $p->name }} ({{ $p->group->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="background:var(--neutral-50);border:1px solid var(--neutral-200);border-radius:6px;padding:.65rem .75rem;font-size:.78rem;color:var(--neutral-600);margin-bottom:.75rem;">
                            ℹ️ Waktu check-in akan diset ke waktu sekarang. Metode tercatat sebagai <strong>Manual</strong>.
                        </div>
                        <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;">
                            ✅ Tambah Absensi
                        </button>
                    </form>
                </div>
            </div>

            {{-- Info sesi --}}
            <div class="card" style="border:1.5px solid var(--warning);background:#fffbf0;">
                <div class="card-body" style="padding:.85rem;">
                    <div style="font-size:.8rem;font-weight:700;color:#b45309;margin-bottom:.5rem;">⚠️ Info Sesi</div>
                    <div style="font-size:.78rem;color:var(--neutral-600);line-height:1.6;">
                        <div><strong>Sesi:</strong> {{ $session->name }}</div>
                        <div><strong>Waktu:</strong> {{ $session->start_time }} – {{ $session->end_time }}</div>
                        <div><strong>Status:</strong>
                            @if($session->is_active)
                                <span style="color:var(--success);font-weight:700;">● Aktif</span>
                            @else
                                <span style="color:var(--neutral-500);">Tidak Aktif</span>
                            @endif
                        </div>
                        <div style="margin-top:.5rem;padding:.5rem;background:#fef3c7;border-radius:4px;font-size:.75rem;">
                            Absensi manual bisa dilakukan kapan saja oleh admin, tidak terbatas waktu sesi.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Belum hadir --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">❌ Belum Hadir ({{ $notAttended->count() }})</span>
                </div>
                <div class="card-body" style="padding:0;max-height:400px;overflow-y:auto;">
                    @if($notAttended->isEmpty())
                        <div style="padding:1.5rem;text-align:center;color:var(--success);font-size:.85rem;font-weight:600;">
                            🎉 Semua peserta sudah hadir!
                        </div>
                    @else
                        @foreach($notAttended as $p)
                        <div style="padding:.6rem 1rem;border-bottom:1px solid var(--neutral-100);display:flex;align-items:center;gap:.5rem;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $p->group->color ?? '#ccc' }};flex-shrink:0;"></span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $p->name }}</div>
                                <div style="font-size:.7rem;color:var(--neutral-400);">{{ $p->group->name ?? '-' }}</div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterPresent() {
    const q = document.getElementById('searchPresent').value.toLowerCase();
    const g = document.getElementById('filterGroup').value;
    document.querySelectorAll('#attendanceTbody tr').forEach(row => {
        const name = row.dataset.name || '';
        const group = row.dataset.group || '';
        const matchName = !q || name.includes(q);
        const matchGroup = !g || group === g;
        row.style.display = (matchName && matchGroup) ? '' : 'none';
    });
}

function filterAddList() {
    const q = document.getElementById('searchAdd').value.toLowerCase();
    const select = document.getElementById('participantSelect');
    Array.from(select.options).forEach(opt => {
        if (!opt.value) return; // keep placeholder
        const name = (opt.dataset.name || '');
        const group = (opt.dataset.group || '');
        opt.style.display = (!q || name.includes(q) || group.includes(q)) ? '' : 'none';
    });
    select.value = ''; // reset selection when filtering
}
</script>
@endpush
@endsection
