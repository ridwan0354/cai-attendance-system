@extends('layouts.app')
@section('title', 'Buat Sesi')
@section('content')
<div style="padding:1.25rem;max-width:600px;margin:0 auto;">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">+ Tambah Sesi Baru</h1>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.sessions.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Nama Sesi *</label>
                <input type="text" name="name" required placeholder="Sesi Pagi Hari 1" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Hari ke- *</label>
                <select name="day_number" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                    <option value="1">Hari 1</option>
                    <option value="2">Hari 2</option>
                    <option value="3">Hari 3</option>
                </select>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Tanggal *</label>
                <input type="date" name="date" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Mulai *</label>
                    <input type="time" name="start_time" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                </div>
                <div>
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Selesai *</label>
                    <input type="time" name="end_time" required style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                </div>
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
