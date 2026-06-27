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
        <a href="{{ route('admin.groups.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🗺️ Grup</a>
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

    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Grup</th>
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
                            <span class="badge badge-primary" style="background: {{ $p->group->color }}">{{ $p->group->name }}</span>
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
