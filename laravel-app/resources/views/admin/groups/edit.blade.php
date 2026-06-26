@extends('layouts.app')
@section('title', 'Edit Grup')
@section('content')
<div style="padding:1.25rem;max-width:600px;margin:0 auto;">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">✏️ Edit Grup: {{ $group->name }}</h1>
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.groups.update', $group) }}" method="POST">
            @csrf @method('PUT')
            @foreach(['name'=>'Nama Grup','pembina_name'=>'Nama Pembina','pembina_phone'=>'No WA Pembina'] as $field => $label)
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">{{ $label }}</label>
                <input type="text" name="{{ $field }}" value="{{ $group->$field }}" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;">
            </div>
            @endforeach
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem;">Warna Grup</label>
                <input type="color" name="color" value="{{ $group->color }}" style="width:60px;height:36px;border:none;cursor:pointer;">
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Update</button>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
