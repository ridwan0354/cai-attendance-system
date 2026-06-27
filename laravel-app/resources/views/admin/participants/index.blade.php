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
        <a href="{{ route('admin.supplies.index') }}" style="padding: 0.5rem 1rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid transparent; color: var(--neutral-500); font-size: .875rem;">🎁 Barang Registrasi</a>
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
            <div style="width: 220px; min-width: 160px;">
                <select name="registration_status" style="width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.875rem; outline: none; background: white; font-family: inherit;">
                    <option value="">— Status Registrasi —</option>
                    <option value="registered" {{ request('registration_status') == 'registered' ? 'selected' : '' }}>Sudah Registrasi</option>
                    <option value="unregistered" {{ request('registration_status') == 'unregistered' ? 'selected' : '' }}>Belum Registrasi</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">🔍 Cari</button>
                @if(request()->filled('search') || request()->filled('group_id') || request()->filled('registration_status'))
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
                        <th>Status Registrasi</th>
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
                            @if($p->registered_at)
                                <div style="font-weight: 600; color: var(--success); font-size: 0.85rem;">
                                    ✅ Terdaftar
                                </div>
                                <div style="font-size: 0.72rem; color: var(--neutral-500); margin-top: 2px;">
                                    🕒 {{ $p->registered_at->format('d/m H:i') }}
                                </div>
                                @if($p->supplies->count() > 0)
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px;">
                                        @foreach($p->supplies as $supply)
                                            <span class="badge" style="background: var(--neutral-100); color: var(--neutral-700); border: 1.5px solid var(--neutral-200); font-size: 0.65rem; padding: 2px 5px; border-radius: 4px; line-height: 1;">
                                                {{ $supply->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="font-size: 0.68rem; color: var(--neutral-400); font-style: italic; margin-top: 4px;">
                                        (Tanpa Barang)
                                    </div>
                                @endif
                            @else
                                <span class="badge badge-danger">❌ Belum</span>
                            @endif
                        </td>
                        <td style="display: flex; gap: 0.25rem; align-items: center; flex-wrap: wrap;">
                            @if($p->face_registered)
                                <button type="button" class="btn btn-sm" onclick="openCheckInModal({{ $p->id }}, '{{ addslashes($p->name) }}')" style="background: var(--success); color: white; border: none; padding: .45rem .75rem;">🎁 Registrasi</button>
                            @else
                                <button type="button" class="btn btn-outline btn-sm" disabled style="opacity: 0.5; cursor: not-allowed; padding: .45rem .75rem;" title="Daftarkan wajah terlebih dahulu melalui Edit">🎁 Registrasi</button>
                            @endif
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

<!-- Modal Registrasi Barang / Check-in -->
<div id="checkInModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200); position: relative;">
        
        <!-- Header -->
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900); border-bottom: 1px solid var(--neutral-150); padding-bottom: 0.5rem;">
            <span id="checkInTitle">🎁 Registrasi Peserta</span>
            <span onclick="closeCheckInModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>
        
        <!-- Step 1: Scan Wajah (Camera Verification) -->
        <div id="checkInStep1">
            <p style="font-size: 0.84rem; color: var(--neutral-500); margin-bottom: 1rem;">
                Harap verifikasi wajah peserta terlebih dahulu dengan mengarahkan wajah ke kamera.
            </p>
            
            <div style="position: relative; width: 100%; background: #000; border-radius: 8px; overflow: hidden; aspect-ratio: 4/3; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center;">
                <video id="checkInVideo" autoplay playsinline muted style="width: 100%; height: 100%; object-fit: cover;"></video>
                <canvas id="checkInCanvas" style="display: none;"></canvas>
                
                <!-- Scanning line effect -->
                <div id="checkInScannerLine" style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary); opacity: 0.8; box-shadow: 0 0 8px var(--primary); display: none;"></div>
                
                <!-- Loading overlays -->
                <div id="checkInLoadingOverlay" style="display: none; position: absolute; z-index: 2; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.65); flex-direction: column; align-items: center; justify-content: center; color: #fff; font-size: 0.875rem;">
                    <div style="border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid var(--primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 0.75rem;"></div>
                    <span id="checkInLoadingText">Memverifikasi Wajah...</span>
                </div>
            </div>
            
            <div id="checkInStatusArea" style="margin-top: 0.5rem; margin-bottom: 1rem; text-align: center; font-size: 0.85rem; font-weight: 600; color: var(--neutral-600); background: var(--neutral-50); padding: 0.65rem; border-radius: 6px; border: 1.5px solid var(--neutral-200); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span>📷 Menghubungkan kamera...</span>
            </div>

            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeCheckInModal()">Batal</button>
            </div>
        </div>
        
        <!-- Step 2: Checklist Barang & Notes -->
        <div id="checkInStep2" style="display: none;">
            <p style="font-size: 0.84rem; color: var(--neutral-500); margin-bottom: 1rem;">
                Wajah terverifikasi (<span id="verifiedConfidence" style="font-weight: 700; color: var(--success);">0% match</span>)! Silakan centang barang yang sudah diambil.
            </p>
            
            <form id="checkInForm" onsubmit="submitCheckIn(event)">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.5rem; color: var(--neutral-700);">Barang yang Diambil</label>
                    <div id="suppliesChecklistContainer" style="display: flex; flex-direction: column; gap: 0.5rem; background: var(--neutral-50); padding: 0.75rem; border-radius: 6px; border: 1.5px solid var(--neutral-200); max-height: 180px; overflow-y: auto;">
                        <!-- Checkboxes dynamically generated -->
                    </div>
                </div>
                
                <div style="margin-bottom: 1.25rem;">
                    <label style="display:block;font-size:.84rem;font-weight:600;margin-bottom:.35rem; color: var(--neutral-700);">Catatan Pendaftaran</label>
                    <textarea id="checkInNotes" rows="3" placeholder="Tambahkan catatan jika perlu (misal: ukuran kaos, dll.)" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--neutral-200);border-radius:6px;font-size:.875rem;font-family:inherit;outline:none;resize:vertical;"></textarea>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--neutral-150); padding-top: 1rem;">
                    <span id="registeredStatusText" style="font-size: 0.75rem; color: var(--neutral-500);"></span>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-outline" onclick="closeCheckInModal()">Batal</button>
                        <button type="submit" class="btn btn-primary" id="checkInSaveBtn">💾 Simpan & Selesai</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes scan {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
.scanner-active-line {
    animation: scan 2s linear infinite;
}
</style>

@push('scripts')
<script>
let checkInStream = null;
let currentParticipantId = null;
let currentParticipantName = '';
let verifiedConfidence = 0;
let checkInScanInterval = null;
let isVerifying = false;

// Audio feedback
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let audioCtx;
function playSuccessSound() {
    if (!audioCtx) audioCtx = new AudioCtx();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain); gain.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, audioCtx.currentTime);
    osc.frequency.setValueAtTime(1100, audioCtx.currentTime + 0.1);
    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
    osc.start(); osc.stop(audioCtx.currentTime + 0.4);
}

async function openCheckInModal(id, name) {
    currentParticipantId = id;
    currentParticipantName = name;
    verifiedConfidence = 0;
    isVerifying = false;

    // Reset UI
    document.getElementById('checkInTitle').textContent = `🎁 Registrasi: ${name}`;
    document.getElementById('checkInStep1').style.display = 'block';
    document.getElementById('checkInStep2').style.display = 'none';
    document.getElementById('checkInLoadingOverlay').style.display = 'none';
    document.getElementById('checkInScannerLine').style.display = 'none';
    document.getElementById('checkInNotes').value = '';
    
    updateStatusArea('📷 Menghubungkan kamera...', 'neutral');

    // Show modal
    document.getElementById('checkInModal').style.display = 'flex';

    // Start camera
    await startCheckInCamera();
}

function closeCheckInModal() {
    stopScanningLoop();
    document.getElementById('checkInModal').style.display = 'none';
    stopCheckInCamera();
}

function updateStatusArea(text, type) {
    const area = document.getElementById('checkInStatusArea');
    if (!area) return;
    area.textContent = text;
    if (type === 'success') {
        area.style.color = 'var(--success)';
        area.style.borderColor = 'var(--success)';
        area.style.background = 'var(--success-lt)';
    } else if (type === 'danger') {
        area.style.color = 'var(--danger)';
        area.style.borderColor = 'var(--danger)';
        area.style.background = 'var(--danger-lt)';
    } else if (type === 'warning') {
        area.style.color = '#7a4f00';
        area.style.borderColor = 'var(--warning)';
        area.style.background = 'var(--warning-lt)';
    } else {
        area.style.color = 'var(--neutral-600)';
        area.style.borderColor = 'var(--neutral-200)';
        area.style.background = 'var(--neutral-50)';
    }
}

async function startCheckInCamera() {
    const video = document.getElementById('checkInVideo');
    
    try {
        checkInStream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' }
        });
        video.srcObject = checkInStream;
        
        video.onloadedmetadata = () => {
            updateStatusArea('🔍 Mencari wajah peserta...', 'warning');
            startScanningLoop();
        };
    } catch (err) {
        console.error("Camera access failed", err);
        updateStatusArea('❌ Gagal mengakses kamera. Berikan izin akses.', 'danger');
    }
}

function stopCheckInCamera() {
    if (checkInStream) {
        checkInStream.getTracks().forEach(track => track.stop());
        checkInStream = null;
    }
    const video = document.getElementById('checkInVideo');
    if (video) video.srcObject = null;
}

function startScanningLoop() {
    stopScanningLoop();
    const scannerLine = document.getElementById('checkInScannerLine');
    if (scannerLine) {
        scannerLine.style.display = 'block';
        scannerLine.classList.add('scanner-active-line');
    }
    checkInScanInterval = setInterval(verifyFace, 1500);
}

function stopScanningLoop() {
    if (checkInScanInterval) {
        clearInterval(checkInScanInterval);
        checkInScanInterval = null;
    }
    const scannerLine = document.getElementById('checkInScannerLine');
    if (scannerLine) {
        scannerLine.style.display = 'none';
        scannerLine.classList.remove('scanner-active-line');
    }
}

async function verifyFace() {
    if (isVerifying) return;
    
    const video = document.getElementById('checkInVideo');
    const canvas = document.getElementById('checkInCanvas');
    
    if (!video || video.readyState !== 4) return;
    
    isVerifying = true;
    updateStatusArea('⚡ Memverifikasi wajah...', 'warning');

    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const base64Image = canvas.toDataURL('image/jpeg').split(',')[1];

    try {
        const response = await fetch(`/admin/participants/${currentParticipantId}/verify-checkin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ image: base64Image })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (data.verified) {
                playSuccessSound();
                stopScanningLoop();
                
                verifiedConfidence = data.confidence;
                document.getElementById('verifiedConfidence').textContent = `${data.confidence}% cocok`;
                document.getElementById('checkInNotes').value = data.notes || '';
                
                const statusText = document.getElementById('registeredStatusText');
                if (data.registered_at) {
                    statusText.innerHTML = `✅ Terdaftar: <span style="font-weight:600;">${data.registered_at}</span>`;
                } else {
                    statusText.textContent = 'Belum pernah registrasi';
                }

                const container = document.getElementById('suppliesChecklistContainer');
                container.innerHTML = '';
                
                if (data.supplies && data.supplies.length > 0) {
                    data.supplies.forEach(item => {
                        const div = document.createElement('div');
                        div.style.display = 'flex';
                        div.style.alignItems = 'center';
                        div.style.gap = '0.5rem';
                        div.style.fontSize = '0.875rem';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.value = item.id;
                        checkbox.checked = item.received;
                        checkbox.id = `supply_check_${item.id}`;
                        checkbox.style.cursor = 'pointer';

                        const label = document.createElement('label');
                        label.htmlFor = `supply_check_${item.id}`;
                        label.textContent = item.name;
                        label.style.cursor = 'pointer';
                        label.style.fontWeight = '500';

                        div.appendChild(checkbox);
                        div.appendChild(label);
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div style="color:var(--neutral-400);text-align:center;font-size:0.8rem;padding:0.5rem;">Tidak ada jenis barang registrasi. Hubungi Admin.</div>';
                }

                document.getElementById('checkInStep1').style.display = 'none';
                document.getElementById('checkInStep2').style.display = 'block';
                stopCheckInCamera();
            } else {
                updateStatusArea('🔍 Wajah tidak cocok/terdeteksi. Memindai ulang...', 'danger');
            }
        } else {
            updateStatusArea('⚠️ Gagal menghubungi server verifikasi. Memindai ulang...', 'danger');
        }
    } catch (err) {
        console.error(err);
        updateStatusArea('⚠️ Gangguan koneksi. Memindai ulang...', 'danger');
    } finally {
        isVerifying = false;
    }
}

async function submitCheckIn(event) {
    event.preventDefault();
    
    const saveBtn = document.getElementById('checkInSaveBtn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Menyimpan...';

    const checkedSupplies = [];
    const checkboxes = document.querySelectorAll('#suppliesChecklistContainer input[type="checkbox"]');
    checkboxes.forEach(cb => {
        if (cb.checked) {
            checkedSupplies.push(parseInt(cb.value));
        }
    });

    const payload = {
        supplies: checkedSupplies,
        notes: document.getElementById('checkInNotes').value,
        confidence: verifiedConfidence
    };

    try {
        const response = await fetch(`/admin/participants/${currentParticipantId}/save-checkin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            closeCheckInModal();
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(data.message || 'Gagal menyimpan registrasi.');
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Simpan & Selesai';
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
        saveBtn.disabled = false;
        saveBtn.textContent = '💾 Simpan & Selesai';
    }
}
</script>
@endpush
@endsection
