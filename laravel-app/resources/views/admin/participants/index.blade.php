@extends('layouts.app')
@section('title', 'Kelola Peserta')

@push('styles')
<style>
    .admin-layout { padding: 1.25rem; max-width: 1200px; margin: 0 auto; }
    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
    .page-title { font-size: 1.25rem; font-weight: 800; color: var(--neutral-900); }
    table { width: 100%; border-collapse: collapse; }
    th { padding: .65rem 1rem; text-align: left; font-size: .75rem; font-weight: 600; color: var(--neutral-500); text-transform: uppercase; letter-spacing: .05em; border-bottom: 2px solid var(--neutral-200); }
    td { padding: .7rem 1rem; font-size: .875rem; border-bottom: 1px solid var(--neutral-100); vertical-align: middle; }
    tr:hover td { background: var(--neutral-50); }
    .participant-name { font-weight: 600; }
    .face-reg-badge { display: inline-flex; align-items: center; gap: 4px; }
    .alert-success { background: var(--success-lt); border: 1px solid var(--success); color: var(--success); padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }
    .alert-warning { background: var(--warning-lt); border: 1px solid var(--warning); color: #7a4f00; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .875rem; }
</style>
@endpush

@section('content')
<div class="admin-layout">
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--neutral-200); margin-bottom: 1.5rem; padding-bottom: 0.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.participants.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid var(--primary); color: var(--primary); font-size: .875rem;">👥 Peserta</a>
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Kelompok</a>
        <a href="{{ route('admin.sessions.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">📅 Sesi</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">👥 Kelola Peserta</h1>
        <a href="{{ route('admin.participants.create') }}" class="btn btn-primary">+ Tambah Peserta</a>
    </div>

    @if(session('success'))
        <div class="alert-success">✅ {{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert-warning">⚠️ {{ session('warning') }}</div>
    @endif

    <!-- Search & Filter -->
    <div class="card" style="margin-bottom: 1.25rem; padding: 1rem; background: white;">
        <form action="{{ route('admin.participants.index') }}" method="GET" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Cari nama peserta..." value="{{ request('search') }}" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; font-family: inherit;">
            </div>
            <div style="width: 220px; min-width: 160px;">
                <select name="group_id" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; background: white; font-family: inherit;">
                    <option value="">— Semua Kelompok —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">🔍 Cari</button>
                @if(request()->filled('search') || request()->filled('group_id'))
                    <a href="{{ route('admin.participants.index') }}" class="btn btn-outline" style="padding: 0.5rem 1.25rem; text-decoration: none; display: inline-flex; align-items: center;">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Kelompok</th>
                        <th>L/P</th>
                        <th>No. WA</th>
                        <th>Wajah Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participants as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td class="participant-name">{{ $p->name }}</td>
                        <td>
                            <span class="badge" style="background: {{ $p->group->color }}; color: #ffffff; text-shadow: 0 1px 2px rgba(0,0,0,0.25);">{{ $p->group->name }}</span>
                        </td>
                        <td>{{ $p->gender ?: '-' }}</td>
                        <td>{{ $p->phone ?: '-' }}</td>
                        <td>
                            @if($p->face_registered)
                                <span class="badge badge-success">✅ Terdaftar</span>
                            @else
                                <span class="badge badge-danger">❌ Belum</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.participants.edit', $p) }}" class="btn btn-outline btn-sm">Edit</a>
                            @if(!$p->face_registered)
                                <a href="{{ route('admin.participants.edit', $p) }}#face-section" class="btn btn-sm" style="background:var(--warning-lt);color:var(--warning);border:1px solid var(--warning);">📸 Daftarkan Wajah</a>
                            @endif
                            <form action="{{ route('admin.participants.destroy', $p) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus peserta ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 1rem;">
        {{ $participants->links() }}
    </div>
</div>
@endsection
