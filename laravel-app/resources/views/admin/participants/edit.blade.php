@extends('layouts.app')
@section('title', 'Edit Peserta')

@push('styles')
<style>
    .admin-layout { padding: 1.25rem; max-width: 700px; margin: 0 auto; }
    .form-group { margin-bottom: 1.1rem; }
    label { display: block; font-size: .84rem; font-weight: 600; margin-bottom: .35rem; color: var(--neutral-700); }
    input, select {
        width: 100%; padding: .55rem .8rem;
        border: 1.5px solid var(--neutral-200); border-radius: 6px;
        font-size: .875rem; font-family: inherit; color: var(--neutral-900);
        outline: none; transition: border-color .15s;
    }
    input:focus, select:focus { border-color: var(--primary); }

    /* Face section */
    .face-section {
        background: var(--neutral-50);
        border: 1.5px dashed var(--neutral-200);
        border-radius: var(--radius);
        padding: 1.25rem;
        margin-top: .5rem;
    }
    .face-section.unregistered { border-color: var(--warning); background: var(--warning-lt); }
    .face-status-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .35rem .75rem; border-radius: 20px; font-size: .8rem; font-weight: 600;
        margin-bottom: .75rem;
    }
    .face-status-badge.ok  { background: var(--success-lt); color: var(--success); }
    .face-status-badge.no  { background: var(--danger-lt);  color: var(--danger); }
    #captureVideo   { width: 100%; max-width: 300px; display: block; margin: .5rem auto; border-radius: 8px; transform: scaleX(-1); }
    #capturePreview { width: 100%; max-width: 300px; display: none; margin: .5rem auto; border-radius: 8px; }
    .webcam-controls { display: flex; gap: .5rem; justify-content: center; margin-top: .5rem; flex-wrap: wrap; }
    .register-result { margin-top: .75rem; padding: .6rem 1rem; border-radius: 8px; font-size: .85rem; display: none; }
    .register-result.success { background: var(--success-lt); color: var(--success); }
    .register-result.error   { background: var(--danger-lt);  color: var(--danger); }
</style>
@endpush

@section('content')
<div class="admin-layout">
    <h1 style="font-size:1.2rem;font-weight:800;margin-bottom:1.25rem;">✏️ Edit Peserta: {{ $participant->name }}</h1>

    <!-- Info form -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-header"><span class="card-title">📋 Data Peserta</span></div>
        <div class="card-body">
            <form action="{{ route('admin.participants.update', $participant) }}" method="POST">
                @csrf @method('PUT')

                <div class="form-group">
                    <label>Nama *</label>
                    <input type="text" name="name" required value="{{ $participant->name }}">
                </div>

                <div class="form-group">
                    <label style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Kelompok *</span>
                        <button type="button" class="btn btn-outline btn-sm" onclick="openAddGroupModal()" style="padding: 2px 8px; font-size: 0.75rem;">
                            ➕ Buat Kelompok Baru
                        </button>
                    </label>
                    <select name="group_id" id="groupIdSelect" required>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ $participant->group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin *</label>
                    <select name="gender" required>
                        <option value="">— Pilih Jenis Kelamin —</option>
                        <option value="Laki-laki" {{ $participant->gender == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ $participant->gender == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>No. WA / WhatsApp Peserta *</label>
                    <input type="text" name="phone" required placeholder="Contoh: 081234567890" value="{{ $participant->phone }}">
                </div>

                <div class="form-group">
                    <label>ID QR Code (Opsional)</label>
                    <input type="text" name="qr_code" placeholder="Scan atau ketik ID QR Code..." value="{{ $participant->qr_code }}">
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Update Data</button>
                    <a href="{{ route('admin.participants.index') }}" class="btn btn-outline">← Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Face Registration Section -->
    <div class="card" id="face-section">
        <div class="card-header">
            <span class="card-title">📸 Registrasi Wajah (Face Recognition)</span>
        </div>
        <div class="card-body">
            <div class="face-section {{ $participant->face_registered ? '' : 'unregistered' }}">

                <div class="face-status-badge {{ $participant->face_registered ? 'ok' : 'no' }}">
                    @if($participant->face_registered)
                        ✅ Wajah sudah terdaftar di sistem
                    @else
                        ❌ Wajah belum terdaftar — kamera tidak bisa mengenali peserta ini
                    @endif
                </div>

                <p style="font-size:.82rem;color:var(--neutral-500);margin-bottom:.75rem;">
                    Arahkan wajah {{ $participant->name }} ke kamera, lalu klik "Ambil Foto" untuk mendaftarkan ke sistem face recognition.
                </p>

                <video id="captureVideo" autoplay playsinline muted style="display:none;"></video>
                <img id="capturePreview" alt="Foto wajah"
                     src="{{ $participant->face_registered ? route('admin.participants.face-image', $participant) : '' }}"
                     style="{{ $participant->face_registered ? 'display:block;' : 'display:none;' }}">

                <div class="webcam-controls">
                    <button type="button" class="btn btn-outline btn-sm" onclick="startCamera()" id="startCamBtn">
                        📷 Buka Kamera
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="captureAndRegister()" id="captureBtn" disabled>
                        📸 Ambil & Daftarkan
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="retake()" id="retakeBtn" style="display:none;">
                        🔄 Ulangi
                    </button>
                </div>

                <div class="register-result" id="registerResult"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buat Kelompok Baru -->
<div id="groupModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 450px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200);">
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900);">
            <span>➕ Tambah Kelompok Baru</span>
            <span onclick="closeAddGroupModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>
        
        <form id="newGroupForm" onsubmit="saveNewGroup(event)">
            <div class="form-group" style="margin-bottom: 0.85rem;">
                <label>Nama Kelompok *</label>
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
                <button type="submit" class="btn btn-primary" id="modalSubmitBtn">💾 Simpan Kelompok</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const PARTICIPANT_ID = {{ $participant->id }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

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
                'X-CSRF-TOKEN': CSRF_TOKEN
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
            
            showToast(`Kelompok ${data.group.name} berhasil dibuat!`, 'success');
            closeAddGroupModal();
        } else {
            const errorMsg = data.message || 'Gagal menyimpan kelompok.';
            alert(errorMsg);
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 Simpan Kelompok';
    }
}

let stream = null;

async function startCamera() {
    const video = document.getElementById('captureVideo');
    const preview = document.getElementById('capturePreview');
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        video.srcObject = stream;
        video.style.display = 'block';
        preview.style.display = 'none';
        document.getElementById('captureBtn').disabled = false;
        document.getElementById('startCamBtn').style.display = 'none';
        document.getElementById('retakeBtn').style.display = 'none';
    } catch(e) {
        showResult('Gagal akses kamera: ' + e.message, 'error');
    }
}

async function captureAndRegister() {
    const video = document.getElementById('captureVideo');
    const preview = document.getElementById('capturePreview');

    // Capture frame
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    const base64 = dataUrl.split(',')[1];

    // Show preview
    preview.src = dataUrl;
    preview.style.display = 'block';
    video.style.display = 'none';
    document.getElementById('captureBtn').disabled = true;
    document.getElementById('captureBtn').textContent = '⏳ Mendaftarkan...';

    // Stop camera
    if (stream) stream.getTracks().forEach(t => t.stop());

    // Send to Laravel → Python DeepFace
    try {
        const res = await fetch(`/admin/participants/${PARTICIPANT_ID}/register-face`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ image: base64 })
        });

        const data = await res.json();

        if (data.success) {
            showResult('✅ ' + data.message + ' — Refresh halaman untuk melihat status terbaru.', 'success');
            // Update status badge
            document.querySelector('.face-status-badge').className = 'face-status-badge ok';
            document.querySelector('.face-status-badge').textContent = '✅ Wajah sudah terdaftar di sistem';
            document.querySelector('.face-section').classList.remove('unregistered');
        } else {
            showResult('❌ Gagal: ' + data.message, 'error');
            document.getElementById('retakeBtn').style.display = 'inline-flex';
        }

        document.getElementById('captureBtn').textContent = '📸 Ambil & Daftarkan';
        document.getElementById('startCamBtn').style.display = 'inline-flex';

    } catch(e) {
        showResult('❌ Error koneksi: ' + e.message, 'error');
        document.getElementById('retakeBtn').style.display = 'inline-flex';
        document.getElementById('captureBtn').textContent = '📸 Ambil & Daftarkan';
    }
}

function retake() {
    document.getElementById('capturePreview').style.display = 'none';
    document.getElementById('captureVideo').style.display = 'none';
    document.getElementById('retakeBtn').style.display = 'none';
    document.getElementById('startCamBtn').style.display = 'inline-flex';
    document.getElementById('captureBtn').disabled = true;
    document.getElementById('registerResult').style.display = 'none';
    startCamera();
}

function showResult(msg, type) {
    const el = document.getElementById('registerResult');
    el.className = 'register-result ' + type;
    el.textContent = msg;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Auto-scroll to face section if #face-section in URL
if (window.location.hash === '#face-section') {
    setTimeout(() => {
        document.getElementById('face-section')?.scrollIntoView({ behavior: 'smooth' });
        startCamera(); // Auto-open camera
    }, 300);
}
</script>
@endpush
