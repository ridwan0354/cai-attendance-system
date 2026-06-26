@extends('layouts.app')
@section('title', 'Edit Sesi')
@section('content')
<div style="padding:1.25rem;max-width:600px;margin:0 auto;">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">✏️ Edit Sesi: {{ $session->name }}</h1>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.sessions.update', $session) }}" method="POST">
            @csrf @method('PUT')
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Nama Sesi *</label>
                <input type="text" name="name" required value="{{ $session->name }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Hari ke-</label>
                <select name="day_number" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                    @for($i=1;$i<=3;$i++)<option value="{{ $i }}" {{ $session->day_number == $i ? 'selected':'' }}>Hari {{ $i }}</option>@endfor
                </select>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Tanggal</label>
                <input type="date" name="date" value="{{ $session->date->format('Y-m-d') }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Mulai</label>
                    <input type="time" name="start_time" value="{{ $session->start_time }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                </div>
                <div>
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Selesai</label>
                    <input type="time" name="end_time" value="{{ $session->end_time }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
                </div>
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Update</button>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
