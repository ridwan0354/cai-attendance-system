@extends('layouts.app')
@section('title', 'Tambah Peserta')

@push('styles')
<style>
    .admin-layout { padding: 1.25rem; max-width: 700px; margin: 0 auto; }
    .form-group { margin-bottom: 1.1rem; }
    label { display: block; font-size: .84rem; font-weight: 600; margin-bottom: .35rem; color: var(--neutral-700); }
    input, select, textarea {
        width: 100%; padding: .55rem .8rem;
        border: 1.5px solid var(--neutral-200); border-radius: 6px;
        font-size: .875rem; font-family: inherit; color: var(--neutral-900);
        outline: none; transition: border-color .15s;
    }
    input:focus, select:focus { border-color: var(--primary); }
    .form-actions { display: flex; gap: .75rem; margin-top: 1.5rem; }

    /* Webcam capture section */
    .webcam-section {
        background: var(--neutral-50);
        border: 1.5px dashed var(--neutral-200);
        border-radius: var(--radius);
        padding: 1rem;
        text-align: center;
    }
    #capturePreview { width: 100%; max-width: 280px; border-radius: 8px; display: none; margin: .5rem auto; }
    #captureVideo   { width: 100%; max-width: 280px; border-radius: 8px; margin: .5rem auto; display: block; transform: scaleX(-1); }
    #faceBase64     { display: none; }
</style>
@endpush

@section('content')
<div class="admin-layout">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">+ Tambah Peserta</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.participants.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="name" required placeholder="Ahmad Fauzi" value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Grup Regional *</span>
                        <button type="button" class="btn btn-outline btn-sm" onclick="openAddGroupModal()" style="padding: 2px 8px; font-size: 0.75rem;">
                            ➕ Buat Grup Baru
                        </button>
                    </label>
                    <select name="group_id" id="groupIdSelect" required>
                        <option value="">— Pilih Grup —</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin *</label>
                    <select name="gender" required>
                        <option value="">— Pilih Jenis Kelamin —</option>
                        <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>No. WA / WhatsApp Peserta *</label>
                    <input type="text" name="phone" required placeholder="Contoh: 081234567890" value="{{ old('phone') }}">
                </div>

                <div class="form-group">
                    <label>Foto untuk Face Recognition</label>
                    <div class="webcam-section">
                        <video id="captureVideo" autoplay playsinline muted></video>
                        <img id="capturePreview" alt="Foto preview">
                        <div style="display:flex;gap:.5rem;justify-content:center;margin-top:.5rem;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="startCamera()">📷 Buka Kamera</button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="capturePhoto()" id="captureBtn" disabled>📸 Ambil Foto</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="retakePhoto()" id="retakeBtn" style="display:none">🔄 Ulang</button>
                        </div>
                        <p style="font-size:.75rem;color:var(--neutral-500);margin-top:.5rem;">
                            Atau upload file:
                            <input type="file" name="photo" accept="image/*" style="width:auto;margin-left:.3rem;" onchange="previewFile(this)">
                        </p>
                    </div>
                    <input type="hidden" name="face_base64" id="faceBase64">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Simpan Peserta</button>
                    <a href="{{ route('admin.participants.index') }}" class="btn btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Buat Grup Baru -->
<div id="groupModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200);">
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900);">
            <span>➕ Tambah Grup Baru</span>
            <span onclick="closeAddGroupModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>
        
        <form id="newGroupForm" onsubmit="saveNewGroup(event)">
            <div class="form-group" style="margin-bottom: 0.85rem;">
                <label>Nama Grup *</label>
                <input type="text" id="modalGroupName" required placeholder="Contoh: Lombok Barat">
            </div>
            
            
            <div class="form-group" style="margin-bottom: 0.85rem;">
                <label>Nama Pembina *</label>
                <input type="text" id="modalGroupPembinaName" required placeholder="Nama Pembina / Utusan">
            </div>
            
            <div class="form-group" style="margin-bottom: 0.85rem;">
                <label>No. WA Pembina *</label>
                <input type="text" id="modalGroupPembinaPhone" required placeholder="Contoh: 081234567890">
            </div>
            
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label>Warna Representasi (UI)</label>
                <input type="color" id="modalGroupColor" value="#0052cc" style="padding: 0; height: 36px; cursor: pointer;">
            </div>
            
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeAddGroupModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">💾 Simpan Grup</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openAddGroupModal() {
    document.getElementById('groupModal').style.display = 'flex';
}

function closeAddGroupModal() {
    document.getElementById('groupModal').style.display = 'none';
    document.getElementById('newGroupForm').reset();
}

async function saveNewGroup(event) {
    event.preventDefault();
    
    const submitBtn = document.getElementById('modalSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan...';
    
    const payload = {
        name: document.getElementById('modalGroupName').value,
        pembina_name: document.getElementById('modalGroupPembinaName').value,
        pembina_phone: document.getElementById('modalGroupPembinaPhone').value,
        color: document.getElementById('modalGroupColor').value,
    };
    
    try {
        const response = await fetch('/admin/groups', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            const select = document.getElementById('groupIdSelect');
            const newOption = document.createElement('option');
            newOption.value = data.group.id;
            newOption.textContent = data.group.name;
            newOption.selected = true;
            select.appendChild(newOption);
            
            showToast(`Grup ${data.group.name} berhasil dibuat!`, 'success');
            closeAddGroupModal();
        } else {
            const errorMsg = data.message || 'Gagal menyimpan grup. Pastikan kode regional unik.';
            alert(errorMsg);
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 Simpan Grup';
    }
}

let stream = null;

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        document.getElementById('captureVideo').srcObject = stream;
        document.getElementById('captureBtn').disabled = false;
    } catch(e) {
        alert('Gagal akses kamera: ' + e.message);
    }
}

function capturePhoto() {
    const video = document.getElementById('captureVideo');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    const base64 = dataUrl.split(',')[1];

    document.getElementById('faceBase64').value = base64;
    document.getElementById('capturePreview').src = dataUrl;
    document.getElementById('capturePreview').style.display = 'block';
    document.getElementById('captureVideo').style.display = 'none';
    document.getElementById('retakeBtn').style.display = 'inline-flex';
    document.getElementById('captureBtn').style.display = 'none';

    if (stream) { stream.getTracks().forEach(t => t.stop()); }
}

function retakePhoto() {
    document.getElementById('capturePreview').style.display = 'none';
    document.getElementById('captureVideo').style.display = 'block';
    document.getElementById('retakeBtn').style.display = 'none';
    document.getElementById('captureBtn').style.display = 'inline-flex';
    document.getElementById('faceBase64').value = '';
    startCamera();
}

function previewFile(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('capturePreview').src = e.target.result;
        document.getElementById('capturePreview').style.display = 'block';
        document.getElementById('captureVideo').style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>
@endpush
