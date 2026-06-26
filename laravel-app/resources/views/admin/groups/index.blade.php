@extends('layouts.app')
@section('title', 'Kelola Grup')
@section('content')
<div style="padding:1.25rem;max-width:900px;margin:0 auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <h1 style="font-size:1.25rem;font-weight:800;">🗺️ Grup Regional</h1>
        <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">+ Tambah Grup</a>
    </div>
    @if(session('success'))
        <div style="background:var(--success-lt);border:1px solid var(--success);color:var(--success);padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.875rem;">✅ {{ session('success') }}</div>
    @endif
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.75rem;">
        @foreach($groups as $g)
        <div class="card" style="border-top: 4px solid {{ $g->color }};">
            <div class="card-body">
                <div style="font-weight:700;font-size:.95rem;margin-bottom:.25rem;">{{ $g->name }}</div>
                <div style="font-size:.75rem;color:var(--neutral-500);">{{ $g->region_code }} • {{ $g->participants_count }} peserta</div>
                <div style="font-size:.8rem;margin-top:.5rem;">👤 {{ $g->pembina_name }}</div>
                <div style="font-size:.78rem;color:var(--neutral-500);">📱 {{ $g->pembina_phone }}</div>
                <div style="display:flex;gap:.5rem;margin-top:.75rem;">
                    <a href="{{ route('admin.groups.edit', $g) }}" class="btn btn-outline btn-sm">Edit</a>
                    <form action="{{ route('admin.groups.destroy', $g) }}" method="POST" onsubmit="return confirm('Hapus grup ini? Semua peserta akan ikut terhapus!')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
