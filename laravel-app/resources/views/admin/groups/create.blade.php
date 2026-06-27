@extends('layouts.app')
@section('title', 'Tambah Kelompok')
@section('content')
<div style="padding:1.25rem;max-width:600px;margin:0 auto;">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">+ Tambah Kelompok Regional</h1>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.groups.store') }}" method="POST">
            @csrf
            @foreach(['name'=>'Nama Kelompok','pembina_name'=>'Nama Pembina','pembina_phone'=>'No WA Pembina (format: 08xxx atau 62xxx)'] as $field => $label)
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">{{ $label }} *</label>
                <input type="text" name="{{ $field }}" required value="{{ old($field) }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            @endforeach
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Warna Kelompok</label>
                <input type="color" name="color" value="#0052cc" style="width:60px;height:36px;border:none;cursor:pointer;">
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Simpan</button>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
