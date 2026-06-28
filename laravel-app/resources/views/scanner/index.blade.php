@extends('layouts.app')
@section('title', 'Scanner Station')

@push('styles')
<style>
    body { background: #0a0e1a; }
    .scanner-layout {
        display: grid;
        grid-template-columns: 1fr 360px;
        height: calc(100vh - 56px);
        gap: 0;
    }

    /* ── Webcam Panel ─────────────────────────────────────────── */
    .webcam-panel {
        position: relative;
        background: #000;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #videoFeed {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1); /* Mirror */
    }
    #faceCanvas {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        pointer-events: none;
    }

    /* Overlay info bar */
    .scanner-overlay-top {
        position: absolute;
        top: 0; left: 0; right: 0;
        padding: 1rem 1.25rem;
        background: linear-gradient(to bottom, rgba(0,0,0,.7) 0%, transparent 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 10;
    }
    .scanner-title {
        color: white;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .status-indicator {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .8rem;
        color: rgba(255,255,255,.8);
    }
    .status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #ccc;
    }
    .status-dot.active  { background: #00e676; box-shadow: 0 0 8px #00e676; animation: pulse-dot 1.5s infinite; }
    .status-dot.error   { background: #ff5252; }
    .status-dot.warning { background: #ff9800; box-shadow: 0 0 8px #ff9800; animation: pulse-dot 1.5s infinite; }
    .status-dot.loading { background: #ffd600; animation: pulse-dot 1s infinite; }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .5; transform: scale(.7); }
    }

    /* Scanning grid overlay */
    .scan-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 5;
    }
    .scan-grid::before, .scan-grid::after {
        content: '';
        position: absolute;
        border: 1px solid rgba(0, 120, 255, .15);
    }
    .scan-grid::before { top: 50%; left: 0; right: 0; transform: translateY(-50%); height: 1px; }
    .scan-grid::after  { left: 50%; top: 0; bottom: 0; transform: translateX(-50%); width: 1px; }

    /* Scan line animation */
    .scan-line {
        position: absolute;
        left: 0; right: 0;
        height: 2px;
        background: linear-gradient(to right, transparent, rgba(0,180,255,.6), transparent);
        z-index: 6;
        animation: scanLine 3s linear infinite;
    }
    @keyframes scanLine {
        0%   { top: 0%; }
        100% { top: 100%; }
    }

    /* Recognition Flash */
    .recognition-flash {
        position: absolute;
        inset: 0;
        background: rgba(0, 230, 118, 0);
        z-index: 20;
        pointer-events: none;
        transition: background .1s;
    }
    .recognition-flash.flash { animation: flashAnim .5s ease; }
    @keyframes flashAnim {
        0%   { background: rgba(0, 230, 118, 0); }
        30%  { background: rgba(0, 230, 118, .25); }
        100% { background: rgba(0, 230, 118, 0); }
    }

    /* Bottom status bar */
    .scanner-overlay-bottom {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 1rem 1.25rem;
        background: linear-gradient(to top, rgba(0,0,0,.8) 0%, transparent 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 10;
        color: rgba(255,255,255,.7);
        font-size: .8rem;
    }
    .face-count-badge {
        background: rgba(255,255,255,.15);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.2);
        color: white;
        padding: .3rem .8rem;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 600;
    }
    .fps-counter { font-size: .75rem; color: rgba(255,255,255,.5); }

    /* ── Side Panel ───────────────────────────────────────────── */
    .side-panel {
        background: #0d1220;
        border-left: 1px solid rgba(255,255,255,.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .side-header {
        padding: 1rem;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .side-header h3 {
        color: rgba(255,255,255,.9);
        font-size: .9rem;
        font-weight: 700;
    }
    .side-header p { color: rgba(255,255,255,.4); font-size: .75rem; margin-top: 2px; }

    /* Session info card */
    .session-card {
        margin: .75rem;
        background: rgba(0,82,204,.2);
        border: 1px solid rgba(0,82,204,.4);
        border-radius: 8px;
        padding: .75rem 1rem;
    }
    .session-card .session-name {
        color: #5c9eff;
        font-weight: 700;
        font-size: .85rem;
    }
    .session-card .session-time {
        color: rgba(255,255,255,.5);
        font-size: .75rem;
        margin-top: 2px;
    }
    .session-card .session-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: .4rem;
        font-size: .7rem;
        font-weight: 600;
        color: #00e676;
    }

    /* Stats row */
    .stats-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .5rem;
        padding: 0 .75rem .75rem;
    }
    .stat-box {
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 8px;
        padding: .6rem .8rem;
        text-align: center;
    }
    .stat-box .stat-num {
        font-size: 1.4rem;
        font-weight: 800;
        color: white;
        line-height: 1;
    }
    .stat-box .stat-num.green { color: #00e676; }
    .stat-box .stat-num.red   { color: #ff5252; }
    .stat-box .stat-label { font-size: .65rem; color: rgba(255,255,255,.4); margin-top: 2px; text-transform: uppercase; letter-spacing: .05em; }

    /* Live feed */
    .live-feed-header {
        padding: .5rem .75rem;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(255,255,255,.35);
        font-weight: 600;
        border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .live-feed {
        flex: 1;
        overflow-y: auto;
        padding: .5rem;
    }
    .live-feed::-webkit-scrollbar { width: 4px; }
    .live-feed::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); }

    .feed-item {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .55rem .6rem;
        border-radius: 7px;
        margin-bottom: .3rem;
        animation: feedSlide .35s ease;
        border: 1px solid transparent;
    }
    .feed-item.new {
        background: rgba(0,230,118,.1);
        border-color: rgba(0,230,118,.2);
    }
    @keyframes feedSlide {
        from { transform: translateY(-10px); opacity: 0; }
        to   { transform: translateY(0);     opacity: 1; }
    }
    .feed-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }
    .feed-info { flex: 1; min-width: 0; }
    .feed-name { font-size: .82rem; font-weight: 600; color: rgba(255,255,255,.9); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .feed-group { font-size: .7rem; color: rgba(255,255,255,.4); }
    .feed-time { font-size: .7rem; color: rgba(255,255,255,.35); white-space: nowrap; }

    .feed-method-badge {
        font-size: .6rem;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 600;
        background: rgba(255,255,255,.1);
        color: rgba(255,255,255,.5);
    }
    .feed-method-badge.face    { background: rgba(0,122,255,.2); color: #5c9eff; }
    .feed-method-badge.manual  { background: rgba(255,165,0,.2); color: #ffa500; }

    /* Scanner controls */
    .scanner-controls {
        padding: .75rem;
        border-top: 1px solid rgba(255,255,255,.08);
        display: flex;
        gap: .5rem;
    }
    .ctrl-btn {
        flex: 1;
        padding: .55rem;
        border-radius: 7px;
        border: none;
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s;
    }
    .ctrl-btn.primary  { background: #0052cc; color: white; }
    .ctrl-btn.primary:hover  { background: #003d99; }
    .ctrl-btn.danger   { background: rgba(255,82,82,.15); border: 1px solid rgba(255,82,82,.3); color: #ff5252; }
    .ctrl-btn.danger:hover   { background: rgba(255,82,82,.25); }

    /* Recognition popup */
    .recognition-popup {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(.8);
        z-index: 30;
        background: rgba(0,0,0,.9);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0,230,118,.4);
        border-radius: 16px;
        padding: 1.5rem 2rem;
        text-align: center;
        min-width: 260px;
        opacity: 0;
        pointer-events: none;
        transition: all .3s cubic-bezier(.34,1.56,.64,1);
    }
    .recognition-popup.show {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
        pointer-events: auto;
    }
    .recognition-popup .check-icon {
        width: 56px; height: 56px;
        background: rgba(0,230,118,.15);
        border: 2px solid #00e676;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto .75rem;
        font-size: 1.5rem;
    }
    .recognition-popup .person-name { font-size: 1.25rem; font-weight: 800; color: white; }
    .recognition-popup .person-group { font-size: .85rem; color: rgba(255,255,255,.5); margin-top: 2px; }
    .recognition-popup .confidence { font-size: .75rem; color: #00e676; margin-top: .5rem; }

    @media (max-width: 900px) {
        .scanner-layout {
            display: flex;
            flex-direction: column;
            height: auto;
            min-height: calc(100vh - 56px);
        }
        .webcam-panel {
            height: 450px;
            width: 100%;
            flex-shrink: 0;
        }
        .side-panel {
            border-left: none;
            border-top: 1px solid rgba(255,255,255,.08);
            height: auto;
            flex: none;
            min-height: 400px;
        }
        .live-feed {
            max-height: 350px;
            overflow-y: auto;
        }
    }

    @media (max-width: 600px) {
        .webcam-panel {
            height: 320px;
        }
        .recognition-popup {
            min-width: 85%;
            padding: 1rem;
        }
        .recognition-popup .person-name {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="scanner-layout">
    <!-- Webcam Panel -->
    <div class="webcam-panel">
        <!-- Top overlay -->
        <div class="scanner-overlay-top">
            <div class="scanner-title">
                <span>📷</span>
                <span>Scanner Station</span>
            </div>
            <div class="status-indicator">
                <div class="status-dot loading" id="statusDot"></div>
                <span id="statusText">Memulai kamera...</span>
            </div>
        </div>

        <!-- Scan visual effects -->
        <div class="scan-grid"></div>
        <div class="scan-line" id="scanLine"></div>
        <div class="recognition-flash" id="recognitionFlash"></div>

        <!-- Video + Canvas -->
        <video id="videoFeed" autoplay playsinline muted></video>
        <canvas id="faceCanvas"></canvas>

        <!-- Recognition popup -->
        <div class="recognition-popup" id="recognitionPopup">
            <div class="check-icon">✓</div>
            <div class="person-name" id="popupName">Ahmad Fauzi</div>
            <div class="person-group" id="popupGroup">Kelompok Lombok Barat</div>
            <div class="confidence" id="popupConfidence">Confidence: 95.2%</div>
        </div>

        <!-- Bottom bar -->
        <div class="scanner-overlay-bottom">
            <div class="face-count-badge" id="faceCountBadge">👤 0 wajah terdeteksi</div>
            <div class="fps-counter" id="fpsCounter">0 fps</div>
        </div>
    </div>

    <!-- Side Panel -->
    <div class="side-panel">
        <div class="side-header">
            <h3>Live Attendance Feed</h3>
            <p>Pemindaian wajah realtime</p>
        </div>

        <!-- Session info -->
        <div class="session-card" id="sessionCard">
            @if($activeSession)
                <div class="session-name">{{ $activeSession->name }}</div>
                <div class="session-time">{{ $activeSession->start_time }} – {{ $activeSession->end_time }}</div>
                <div class="session-status">
                    <div style="width:6px;height:6px;border-radius:50%;background:#00e676;"></div>
                    Sesi Aktif • Hari ke-{{ $activeSession->day_number }}
                </div>
            @else
                <div class="session-name" style="color: #ff5252;">Belum ada sesi aktif</div>
                <div class="session-time">Aktifkan sesi di panel Admin</div>
            @endif
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num green" id="statPresent">0</div>
                <div class="stat-label">Hadir</div>
            </div>
            <div class="stat-box">
                <div class="stat-num red" id="statAbsent">-</div>
                <div class="stat-label">Belum</div>
            </div>
        </div>

        <!-- Live feed list -->
        <div class="live-feed-header">Live Check-in</div>
        <div class="live-feed" id="liveFeed">
            <div style="text-align:center; padding: 2rem; color: rgba(255,255,255,.2); font-size: .8rem;">
                Menunggu data absensi...
            </div>
        </div>

        <!-- Controls -->
        <div class="scanner-controls">
            <button class="ctrl-btn primary" id="toggleScanBtn" onclick="toggleScanning()">
                ⏸ Pause Scan
            </button>
            <button class="ctrl-btn danger" onclick="openManualEntry()">
                ✏️ Manual
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- face-api.js for browser-side face detection -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<!-- jsQR for browser-side QR Code decoding -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

<script>
// ── Config ──────────────────────────────────────────────────────────────────
const SESSION_ID  = {{ $activeSession?->id ?? 'null' }};
const CSRF_TOKEN  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── State ────────────────────────────────────────────────────────────────────
let isScanning    = true;
let isDetecting   = false;
let faceApiLoaded = false;
let presentCount  = 0;
let seenParticipants = new Set();
let trackedFaces  = [];
let frameCount    = 0;
let lastFpsTime   = Date.now();

// ── DOM ──────────────────────────────────────────────────────────────────────
const video         = document.getElementById('videoFeed');
const canvas        = document.getElementById('faceCanvas');
const ctx           = canvas.getContext('2d');
const statusDot     = document.getElementById('statusDot');
const statusText    = document.getElementById('statusText');
const faceCount     = document.getElementById('faceCountBadge');
const liveFeed      = document.getElementById('liveFeed');
const popup         = document.getElementById('recognitionPopup');
const flash         = document.getElementById('recognitionFlash');

// ── Audio feedback ───────────────────────────────────────────────────────────
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

// ── Status helpers ────────────────────────────────────────────────────────────
function setStatus(state, text) {
    statusDot.className = 'status-dot ' + state;
    statusText.textContent = text;
}

// ── Camera init ───────────────────────────────────────────────────────────────
async function initCamera() {
    setStatus('loading', 'Mengakses kamera...');
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: 'user' },
            audio: false
        });
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            setStatus('active', 'Kamera aktif');
            loadFaceApi();
        };
    } catch (err) {
        setStatus('error', 'Gagal akses kamera: ' + err.message);
        console.error(err);
    }
}

// ── Load face-api.js models (for browser-side detection) ─────────────────────
async function loadFaceApi() {
    setStatus('loading', 'Memuat model deteksi...');
    try {
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        ]);
        faceApiLoaded = true;
        if (!SESSION_ID) {
            setStatus('warning', 'Sesi tidak aktif! Aktifkan sesi di panel Admin.');
        } else {
            setStatus('active', 'Siap memindai');
        }
        startScanning();
    } catch (err) {
        setStatus('error', 'Gagal memuat model deteksi: ' + err.message);
        console.error(err);
    }
}

// ── Scanning loop ─────────────────────────────────────────────────────────────
let isScanningQR = false;
let qrScanTimeout = null;
let isProcessingQR = false;

function startScanning() {
    if (!isDetecting) {
        isDetecting = true;
        detectionLoop();
        requestAnimationFrame(drawLoop);
        startQRScanning();
    }
}

function stopScanning() {
    isDetecting = false;
    trackedFaces = [];
    if (ctx) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    stopQRScanning();
}

function startQRScanning() {
    isScanningQR = true;
    qrScanLoop();
}

function stopQRScanning() {
    isScanningQR = false;
    if (qrScanTimeout) {
        clearTimeout(qrScanTimeout);
        qrScanTimeout = null;
    }
}

async function qrScanLoop() {
    if (!isScanningQR) return;

    if (isScanning && video.readyState === 4) {
        try {
            const qrCanvas = document.createElement('canvas');
            qrCanvas.width = video.videoWidth;
            qrCanvas.height = video.videoHeight;
            const qrCtx = qrCanvas.getContext('2d');
            qrCtx.drawImage(video, 0, 0, qrCanvas.width, qrCanvas.height);

            const imageData = qrCtx.getImageData(0, 0, qrCanvas.width, qrCanvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: "dontInvert"
            });

            if (code && code.data && !isProcessingQR) {
                isProcessingQR = true;
                await processQRCheckIn(code.data);
            }
        } catch (e) {
            console.error('QR decoding error:', e);
        }
    }

    qrScanTimeout = setTimeout(qrScanLoop, 300);
}

async function processQRCheckIn(qrCode) {
    if (!SESSION_ID) {
        isProcessingQR = false;
        return;
    }

    try {
        const response = await fetch('/api/attendance/qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({ qr_code: qrCode, session_id: SESSION_ID })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            handleRecognition({
                participant_id: data.match.participant_id,
                participant_name: data.participant_name,
                group_name: data.group_name,
                group_color: data.group_color,
                method: 'qr'
            });
            
            setTimeout(() => {
                isProcessingQR = false;
            }, 3500);
        } else {
            if (data.message) {
                showToast(`❌ ${data.message}`, 'error');
            }
            setTimeout(() => {
                isProcessingQR = false;
            }, 2000);
        }
    } catch (err) {
        console.error(err);
        setTimeout(() => {
            isProcessingQR = false;
        }, 2000);
    }
}

function toggleScanning() {
    isScanning = !isScanning;
    const btn = document.getElementById('toggleScanBtn');
    if (isScanning) {
        startScanning();
        btn.textContent = '⏸ Pause Scan';
        btn.className = 'ctrl-btn primary';
        if (!SESSION_ID) {
            setStatus('warning', 'Sesi tidak aktif! Aktifkan sesi di panel Admin.');
        } else {
            setStatus('active', 'Siap memindai');
        }
    } else {
        stopScanning();
        btn.textContent = '▶ Resume Scan';
        btn.className = 'ctrl-btn danger';
        setStatus('loading', 'Scan dijeda');
    }
}

// ── Detection Loop ────────────────────────────────────────────────────────────
async function detectionLoop() {
    if (!isDetecting) return;

    if (isScanning && faceApiLoaded && video.readyState === 4) {
        try {
            const detections = await faceapi.detectAllFaces(
                video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.35 })
            );
            
            faceCount.textContent = `👤 ${detections.length} wajah terdeteksi`;
            trackDetections(detections);
        } catch (e) {
            console.error('Detection error:', e);
        }
    }
    
    // Run detection loop again after 150ms to save CPU
    setTimeout(detectionLoop, 150);
}

// ── Track detections across frames ────────────────────────────────────────────
function trackDetections(detections) {
    const now = Date.now();

    detections.forEach(det => {
        const { x, y, width, height } = det.box;
        const cx = x + width / 2;
        const cy = y + height / 2;

        // Find closest tracked face
        let closestFace = null;
        let minDistance = Infinity;

        trackedFaces.forEach(face => {
            const fcx = face.box.x + face.box.width / 2;
            const fcy = face.box.y + face.box.height / 2;
            const dist = Math.sqrt((cx - fcx) ** 2 + (cy - fcy) ** 2);
            if (dist < minDistance) {
                minDistance = dist;
                closestFace = face;
            }
        });

        // Threshold for matching: 90px center-to-center
        if (closestFace && minDistance < 90) {
            // Update existing face
            closestFace.box = { x, y, width, height };
            closestFace.lastSeen = now;
        } else {
            // Register new face
            const newId = 'face_' + Math.random().toString(36).substr(2, 9);
            const newFace = {
                id: newId,
                box: { x, y, width, height },
                status: 'scanning',
                name: '',
                group: '',
                color: '',
                lastSeen: now,
                lastSentTime: 0
            };
            trackedFaces.push(newFace);

            // Trigger recognition immediately!
            recognizeFace(newFace);
        }
    });

    // For existing faces, if they are 'unknown' or 'failed' and haven't been checked for 4 seconds, retry
    trackedFaces.forEach(face => {
        if ((face.status === 'unknown' || face.status === 'failed') && (now - face.lastSentTime > 4000)) {
            face.status = 'scanning';
            recognizeFace(face);
        }
    });

    // Remove faces not seen for > 1.2 seconds
    trackedFaces = trackedFaces.filter(face => {
        return (now - face.lastSeen) < 1200;
    });
}

// ── Face recognition request ──────────────────────────────────────────────────
async function recognizeFace(face) {
    if (!SESSION_ID || !isScanning) return;
    
    // Mark as processing
    face.status = 'processing';
    face.lastSentTime = Date.now();
    
    // Crop face locally from video feed
    const { x, y, width, height } = face.box;
    const cropCanvas = document.createElement('canvas');
    cropCanvas.width = 160;
    cropCanvas.height = 160;
    const cropCtx = cropCanvas.getContext('2d');

    // Add 15% padding around the face box
    const padX = width * 0.15;
    const padY = height * 0.15;
    const sx = Math.max(0, x - padX);
    const sy = Math.max(0, y - padY);
    const sw = Math.min(video.videoWidth - sx, width + padX * 2);
    const sh = Math.min(video.videoHeight - sy, height + padY * 2);

    try {
        cropCtx.drawImage(video, sx, sy, sw, sh, 0, 0, 160, 160);
        const base64 = cropCanvas.toDataURL('image/jpeg', 0.85).split(',')[1];

        // Send to backend
        const res = await fetch('/api/attendance/face', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({ image: base64, session_id: SESSION_ID })
        });
        
        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }
        
        const data = await res.json();

        // Check if the face is still being tracked (might have walked away during the API call)
        const currentFace = trackedFaces.find(f => f.id === face.id);
        if (!currentFace) return;

        if (data.success && data.recognized && data.matches.length > 0) {
            const match = data.matches[0];
            
            // Check if they are already checked in (duplicate)
            if (match.already_present || seenParticipants.has(match.participant_id)) {
                currentFace.status = 'duplicate';
                currentFace.name = match.participant_name;
                seenParticipants.add(match.participant_id);
                return;
            }

            // Mark as recognized
            currentFace.status = 'recognized';
            currentFace.name = match.participant_name;
            currentFace.group = match.group_name;
            currentFace.color = match.group_color;

            // Handle success trigger
            handleRecognition(match);
        } else {
            // Not recognized/Unknown
            currentFace.status = 'unknown';
        }
    } catch (err) {
        console.warn('Recognition call failed:', err.message);
        const currentFace = trackedFaces.find(f => f.id === face.id);
        if (currentFace) {
            currentFace.status = 'failed';
        }
    }
}

// ── Handle recognition result ─────────────────────────────────────────────────
function handleRecognition(match) {
    if (seenParticipants.has(match.participant_id)) return; // Already shown this session
    seenParticipants.add(match.participant_id);

    // Flash effect
    flash.classList.add('flash');
    setTimeout(() => flash.classList.remove('flash'), 500);

    // Sound
    playSuccessSound();

    // Popup
    document.getElementById('popupName').textContent = match.participant_name;
    document.getElementById('popupGroup').textContent = 'Kelompok ' + match.group_name;
    document.getElementById('popupConfidence').textContent =
        match.confidence_score ? `Confidence: ${match.confidence_score}%` : '';
    popup.classList.add('show');
    setTimeout(() => popup.classList.remove('show'), 3000);

    // Add to live feed
    addToFeed(match);

    // Update counter
    presentCount++;
    document.getElementById('statPresent').textContent = presentCount;

    // Toast
    showToast(`✅ ${match.participant_name} — ${match.group_name}`, 'success');
}

// ── Draw face bounding boxes ──────────────────────────────────────────────────
function drawFaceBoxes() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (trackedFaces.length === 0) return;

    trackedFaces.forEach(face => {
        const { x, y, width, height } = face.box;
        
        let color = '#007aff'; // blue for scanning
        let text = 'Scanning...';
        let isGlow = true;

        if (!SESSION_ID) {
            color = '#ff9800'; // orange for warning
            text = 'Sesi Belum Aktif';
            isGlow = false;
        } else if (face.status === 'processing') {
            color = '#ffd600'; // yellow for processing
            text = 'Mengidentifikasi...';
        } else if (face.status === 'recognized' || face.status === 'duplicate') {
            color = '#00e676'; // green for recognized
            text = face.name;
            isGlow = true;
        } else if (face.status === 'unknown') {
            color = '#ff5252'; // red for unknown
            text = 'Tidak Dikenal';
            isGlow = false;
        } else if (face.status === 'failed') {
            color = '#ff5252';
            text = 'Gagal';
            isGlow = false;
        }

        // Draw bounding box corners with glow
        if (isGlow) {
            ctx.shadowColor = color;
            ctx.shadowBlur = 15;
        } else {
            ctx.shadowBlur = 0;
        }

        ctx.strokeStyle = color;
        ctx.lineWidth = 3;

        // Draw corner brackets
        const corner = Math.min(20, width * 0.25);
        ctx.beginPath();
        // Top-left
        ctx.moveTo(x + corner, y); ctx.lineTo(x, y); ctx.lineTo(x, y + corner);
        // Top-right
        ctx.moveTo(x + width - corner, y); ctx.lineTo(x + width, y); ctx.lineTo(x + width, y + corner);
        // Bottom-left
        ctx.moveTo(x, y + height - corner); ctx.lineTo(x, y + height); ctx.lineTo(x + corner, y + height);
        // Bottom-right
        ctx.moveTo(x + width - corner, y + height); ctx.lineTo(x + width, y + height); ctx.lineTo(x + width, y + height - corner);
        ctx.stroke();

        // Draw text label container
        ctx.shadowBlur = 0;
        ctx.fillStyle = color;
        
        ctx.font = '600 12px Inter, sans-serif';
        const textWidth = ctx.measureText(text).width;
        const padding = 16;
        const rectWidth = textWidth + padding;
        
        // Label background
        ctx.fillRect(x, y - 24, Math.max(80, rectWidth), 22);
        
        // Label text
        ctx.fillStyle = '#ffffff';
        
        // Mirror the text drawing so it reads correctly on a mirrored canvas
        ctx.save();
        // Move to the center of the label text area
        ctx.translate(x + Math.max(80, rectWidth) / 2, y - 13);
        // Flip horizontally
        ctx.scale(-1, 1);
        // Draw text centered
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 0, 0);
        ctx.restore();
    });
}

// ── Draw loop (FPS counter) ───────────────────────────────────────────────────
function drawLoop() {
    if (!isDetecting) return;
    
    frameCount++;
    const now = Date.now();
    if (now - lastFpsTime > 1000) {
        document.getElementById('fpsCounter').textContent = `${frameCount} fps`;
        frameCount = 0;
        lastFpsTime = now;
    }
    
    if (isScanning) {
        drawFaceBoxes();
    } else {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    
    requestAnimationFrame(drawLoop);
}

// ── Add item to live feed ─────────────────────────────────────────────────────
function addToFeed(match) {
    if (!match.participant_id) return;
    
    // Prevent duplicate entries in the UI
    const existingItem = document.getElementById(`feed-participant-${match.participant_id}`);
    if (existingItem) return;

    // Remove placeholder
    const placeholder = liveFeed.querySelector('[data-placeholder]');
    if (placeholder) placeholder.remove();

    const initials = match.participant_name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const color = match.group_color || '#0052cc';
    const method = match.method || 'face';
    const time = match.time || match.check_in_time || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    const item = document.createElement('div');
    item.className = 'feed-item new';
    item.id = `feed-participant-${match.participant_id}`;
    item.innerHTML = `
        <div class="feed-avatar" style="background: ${color}">${initials}</div>
        <div class="feed-info">
            <div class="feed-name">${match.participant_name}</div>
            <div class="feed-group">${match.group_name} <span class="feed-method-badge ${method}">${method}</span></div>
        </div>
        <div class="feed-time">${time}</div>
    `;

    liveFeed.insertBefore(item, liveFeed.firstChild);
    setTimeout(() => item.classList.remove('new'), 3000);

    // Keep max 50 items
    while (liveFeed.children.length > 50) {
        liveFeed.removeChild(liveFeed.lastChild);
    }
}

// ── WebSocket (Reverb) — listen for other scanners ────────────────────────────
if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('attendance')
        .listen('.attendance.recorded', (e) => {
            if (!seenParticipants.has(e.participant_id)) {
                seenParticipants.add(e.participant_id);
                addToFeed({ 
                    participant_id: e.participant_id, 
                    participant_name: e.participant_name, 
                    group_name: e.group_name, 
                    group_color: e.group_color, 
                    method: e.method,
                    time: e.check_in_time
                });
                presentCount++;
                document.getElementById('statPresent').textContent = presentCount;
            }
        });
}

// ── Manual entry placeholder ──────────────────────────────────────────────────
function openManualEntry() {
    window.location.href = '/admin/participants';
}

// ── Load stats on start ───────────────────────────────────────────────────────
async function loadInitialStats() {
    if (!SESSION_ID) return;
    try {
        const res = await fetch('/api/dashboard/stats?session_id=' + SESSION_ID);
        const data = await res.json();
        if (data.success) {
            presentCount = data.total_present;
            document.getElementById('statPresent').textContent = data.total_present;
            document.getElementById('statAbsent').textContent  = data.total_absent;

            // Populate feed with recent
            data.recent_attendances.forEach(a => {
                seenParticipants.add(a.participant_id);
                addToFeed({
                    participant_id: a.participant_id,
                    participant_name: a.name,
                    group_name: a.group,
                    group_color: a.group_color,
                    method: a.method,
                    time: a.time
                });
            });
        }
    } catch(e) {}
}

// ── Init ──────────────────────────────────────────────────────────────────────
initCamera();
loadInitialStats();
</script>
@endpush
