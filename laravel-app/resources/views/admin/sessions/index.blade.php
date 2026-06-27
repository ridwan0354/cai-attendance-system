@extends('layouts.app')
@section('title', 'Kelola Sesi')

@section('content')
<div style="padding:1.25rem;max-width:900px;margin:0 auto;">
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">📅 Sesi</a>
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang Registrasi</a>
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
                        <td style="padding:.7rem 1rem;font-size:.875rem;border-bottom:1px solid var(--neutral-100);">
                            @if(!$s->is_active)
                                <form action="{{ route('admin.sessions.activate', $s) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-success btn-sm">▶ Aktifkan</button>
                                </form>
                            @else
                                <form action="{{ route('admin.sessions.deactivate', $s) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-outline btn-sm">⏹ Nonaktifkan</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.sessions.edit', $s) }}" class="btn btn-outline btn-sm">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
