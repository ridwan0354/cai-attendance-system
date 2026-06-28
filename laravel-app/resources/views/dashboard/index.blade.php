@extends('layouts.app')
@section('title', 'Dashboard Realtime')

@push('styles')
<style>
    .dash-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        grid-template-rows: auto 1fr;
        gap: 1rem;
        padding: 1.25rem;
        max-width: 1600px;
        margin: 0 auto;
        min-height: calc(100vh - 56px);
    }

    /* ── Header stats ─────────────────────────────────── */
    .stats-header {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    .stat-card {
        background: white;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius);
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; bottom: 0;
        width: 4px;
    }
    .stat-card.blue::before   { background: var(--primary); }
    .stat-card.green::before  { background: var(--success); }
    .stat-card.red::before    { background: var(--danger); }
    .stat-card.orange::before { background: var(--warning); }

    .stat-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .stat-card.blue   .stat-icon { background: var(--primary-lt); }
    .stat-card.green  .stat-icon { background: var(--success-lt); }
    .stat-card.red    .stat-icon { background: var(--danger-lt); }
    .stat-card.orange .stat-icon { background: var(--warning-lt); }

    .stat-info { flex: 1; min-width: 0; }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--neutral-900);
        line-height: 1;
        transition: all .3s;
    }
    .stat-label { font-size: .75rem; color: var(--neutral-500); margin-top: 3px; text-transform: uppercase; letter-spacing: .05em; }

    /* ── Main grid ────────────────────────────────────── */
    .main-left  { display: flex; flex-direction: column; gap: 1rem; }
    .main-right { display: flex; flex-direction: column; gap: 1rem; }

    /* ── Group cards ─────────────────────────────────── */
    .groups-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .75rem;
    }
    .group-card {
        background: white;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius);
        padding: .85rem 1rem;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
        transition: transform .15s, box-shadow .15s;
    }
    .group-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
    .group-card-accent {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .group-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .6rem; }
    .group-name { font-weight: 700; font-size: .88rem; color: var(--neutral-900); }
    .group-pct  { font-size: .8rem; font-weight: 800; color: var(--neutral-700); }

    .group-progress-bar {
        height: 6px;
        background: var(--neutral-100);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: .5rem;
    }
    .group-progress-fill {
        height: 100%;
        border-radius: 3px;
        transition: width .5s ease;
    }
    .group-counts {
        display: flex;
        gap: .5rem;
        font-size: .72rem;
        color: var(--neutral-500);
    }
    .group-counts strong { color: var(--neutral-700); }
    .group-region { font-size: .7rem; color: var(--neutral-500); margin-top: .2rem; }

    /* ── Live feed (right panel) ─────────────────────── */
    .live-feed-panel {
        background: white;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        max-height: calc(100vh - 220px);
    }
    .live-panel-header {
        padding: .85rem 1rem;
        border-bottom: 1px solid var(--neutral-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .live-panel-title { font-weight: 700; font-size: .9rem; }
    .live-badge {
        background: #ff5252;
        color: white;
        font-size: .65rem;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 700;
        animation: pulseLive 2s infinite;
    }
    @keyframes pulseLive {
        0%, 100% { opacity: 1; }
        50%       { opacity: .5; }
    }
    .live-list { flex: 1; overflow-y: auto; padding: .5rem; }
    .live-item {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .55rem .65rem;
        border-radius: 8px;
        margin-bottom: .3rem;
        transition: background .15s;
    }
    .live-item:hover { background: var(--neutral-50); }
    .live-item.new-entry { animation: entrySlide .4s ease; background: var(--success-lt); }
    @keyframes entrySlide {
        from { transform: translateY(-8px); opacity: 0; background: var(--success-lt); }
    }
    .live-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 700; color: white;
        flex-shrink: 0;
    }
    .live-info { flex: 1; min-width: 0; }
    .live-name  { font-size: .84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .live-group { font-size: .7rem; color: var(--neutral-500); }
    .live-time  { font-size: .7rem; color: var(--neutral-500); white-space: nowrap; }

    /* ── Session info ─────────────────────────────────── */
    .session-info-card {
        background: white;
        border: 1px solid var(--neutral-200);
        border-radius: var(--radius);
        padding: 1rem;
        box-shadow: var(--shadow);
    }
    .session-info-name { font-weight: 700; font-size: .95rem; margin-bottom: .25rem; }
    .session-info-time { font-size: .8rem; color: var(--neutral-500); }
    .session-progress {
        margin-top: .75rem;
        height: 8px;
        background: var(--neutral-100);
        border-radius: 4px;
        overflow: hidden;
    }
    .session-progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(to right, var(--primary), #6554c0);
        transition: width 1s ease;
    }
    .session-info-meta {
        display: flex;
        justify-content: space-between;
        margin-top: .4rem;
        font-size: .72rem;
        color: var(--neutral-500);
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .dash-layout { grid-template-columns: 1fr; }
        .stats-header { grid-template-columns: repeat(2, 1fr); }
        .groups-grid  { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .stats-header { grid-template-columns: 1fr 1fr; }
        .dash-layout { padding: .75rem; gap: .75rem; }
    }
</style>
@endpush

@section('content')
<div class="dash-layout">
    <!-- ── Stats Header ── -->
    <div class="stats-header">
        <div class="stat-card blue">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-value" id="totalParticipants">-</div>
                <div class="stat-label">Total Peserta</div>
                <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 5px; font-weight: 600;">
                    👨 <span id="totalMale">{{ $totalMale }}</span> L &nbsp;|&nbsp; 👩 <span id="totalFemale">{{ $totalFemale }}</span> P
                </div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-value" id="totalPresent">-</div>
                <div class="stat-label">Sudah Hadir</div>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <div class="stat-value" id="totalAbsent">-</div>
                <div class="stat-label">Belum Hadir</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-value" id="percentage">-</div>
                <div class="stat-label">Persentase Hadir</div>
            </div>
        </div>
    </div>

    <!-- ── Main Left ── -->
    <div class="main-left">
        <!-- Session info -->
        <div class="session-info-card" id="sessionInfoCard">
            @if($activeSession)
                <div class="session-info-name">{{ $activeSession->name }}</div>
                <div class="session-info-time">
                    Hari ke-{{ $activeSession->day_number }} &bull;
                    {{ \Carbon\Carbon::parse($activeSession->date)->format('d M Y') }} &bull;
                    {{ $activeSession->start_time }} – {{ $activeSession->end_time }}
                </div>
                <div class="session-progress">
                    <div class="session-progress-fill" id="sessionProgress" style="width: 0%"></div>
                </div>
                <div class="session-info-meta">
                    <span id="sessionTimeLeft">Menghitung...</span>
                    <span class="badge badge-success">● Sesi Aktif</span>
                </div>
            @else
                <div class="session-info-name">Belum ada sesi aktif</div>
                <div class="session-info-time">Aktifkan sesi di panel <a href="/admin/sessions">Admin → Sessions</a></div>
            @endif
        </div>

        <!-- Group cards grid -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">📍 Status Per Kelompok Regional</span>
                <span class="badge badge-primary" id="groupUpdateBadge">Live</span>
            </div>
            <div class="card-body" style="padding: .75rem;">
                <div class="groups-grid" id="groupsGrid">
                    @foreach($groups as $group)
                    <div class="group-card" id="group-{{ $group->id }}">
                        <div class="group-card-accent" style="background: {{ $group->color }}"></div>
                        <div class="group-card-header">
                            <div class="group-name">{{ $group->name }}</div>
                            <div class="group-pct" id="group-pct-{{ $group->id }}">-</div>
                        </div>
                        <div class="group-progress-bar">
                            <div class="group-progress-fill" id="group-bar-{{ $group->id }}"
                                 style="background: {{ $group->color }}; width: 0%">
                            </div>
                        </div>
                        <div class="group-counts">
                            <span>✅ <strong id="group-present-{{ $group->id }}">0</strong></span>
                            <span>❌ <strong id="group-absent-{{ $group->id }}">0</strong></span>
                            <span>/ <strong>{{ $group->participants_count }}</strong></span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Event Sessions Card -->
        <div class="card" style="margin-top: 1rem;">
            <div class="card-header">
                <span class="card-title">📅 Jadwal & Monitor Sesi Acara</span>
            </div>
            <div class="card-body" style="padding: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem;">
                    @foreach($sessions as $session)
                        @php
                            $isCurrentActive = $activeSession && $activeSession->id === $session->id;
                        @endphp
                        <div onclick="openSessionDetailModal({{ $session->id }}, '{{ addslashes($session->name) }}')" 
                             style="cursor: pointer; background: white; border: 1.5px solid {{ $isCurrentActive ? 'var(--primary)' : 'var(--neutral-200)' }}; border-radius: 8px; padding: 0.85rem; box-shadow: var(--shadow); position: relative; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s;"
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)';"
                             onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow)';">
                            
                            @if($isCurrentActive)
                                <span style="position: absolute; top: 0.5rem; right: 0.5rem; background: var(--success); color: white; font-size: 0.62rem; font-weight: 700; padding: 1px 6px; border-radius: 10px; animation: pulseLive 2s infinite;">AKTIF</span>
                            @endif
                            
                            <div>
                                <div style="font-weight: 800; font-size: 0.88rem; color: var(--neutral-900); padding-right: 2.5rem;">{{ $session->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 4px;">
                                    Hari ke-{{ $session->day_number }} &bull; {{ \Carbon\Carbon::parse($session->date)->format('d M') }}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--neutral-500); margin-top: 2px;">
                                    🕒 {{ $session->start_time }} – {{ $session->end_time }}
                                </div>
                            </div>
                            
                            <div style="margin-top: 0.75rem; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--neutral-150); padding-top: 0.5rem; font-size: 0.75rem; color: var(--neutral-600); font-weight: 600;">
                                <span>Detail Absensi</span>
                                <span style="color: var(--primary);">➔</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- ── Main Right ── -->
    <div class="main-right">
        <!-- Live feed -->
        <div class="live-feed-panel">
            <div class="live-panel-header">
                <span class="live-panel-title">🔴 Live Check-in</span>
                <span class="live-badge">LIVE</span>
            </div>
            <div class="live-list" id="liveList">
                <div style="padding: 2rem; text-align: center; color: var(--neutral-500); font-size: .85rem;">
                    Menunggu peserta masuk...
                </div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">⚡ Aksi Cepat</span>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: .5rem;">
                <a href="{{ route('scanner') }}" class="btn btn-primary" style="width:100%; justify-content:center;">
                    📷 Buka Scanner
                </a>
                <a href="{{ route('admin.participants.index') }}" class="btn btn-outline" style="width:100%; justify-content:center;">
                    👥 Kelola Peserta
                </a>
                <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline" style="width:100%; justify-content:center;">
                    📅 Kelola Sesi
                </a>
            </div>
        </div>
</div>

<!-- Modal Detail Sesi -->
<div id="sessionDetailModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content card" style="background-color: #fff; margin: auto; padding: 1.5rem; border-radius: 8px; width: 100%; max-width: 650px; box-shadow: var(--shadow-lg); border: 1px solid var(--neutral-200); position: relative; display: flex; flex-direction: column; max-height: 85vh;">
        
        <!-- Header -->
        <h3 style="margin-bottom: 1rem; font-weight: 800; font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center; color: var(--neutral-900); border-bottom: 1px solid var(--neutral-150); padding-bottom: 0.5rem; flex-shrink: 0;">
            <span id="detailModalTitle">📅 Detail Absensi Sesi</span>
            <span onclick="closeSessionDetailModal()" style="cursor: pointer; font-size: 1.25rem; color: var(--neutral-500);">&times;</span>
        </h3>
        
        <!-- Filter Form & Stats Header -->
        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; flex-shrink: 0; justify-content: space-between; background: var(--neutral-50); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--neutral-200);">
            <!-- Stats -->
            <div style="display: flex; gap: 0.75rem; font-size: 0.8rem; font-weight: 700; color: var(--neutral-700);">
                <span>Total: <span id="detailTotalCount">0</span></span>
                <span style="color: var(--success);">Hadir: <span id="detailPresentCount">0</span></span>
                <span style="color: var(--danger);">Belum: <span id="detailAbsentCount">0</span></span>
            </div>
            
            <!-- Group filter dropdown -->
            <div style="width: 200px;">
                <select id="detailGroupFilter" onchange="filterSessionDetail()" style="width: 100%; padding: 0.4rem 0.6rem; border: 1.5px solid var(--neutral-200); border-radius: 6px; font-size: 0.8rem; outline: none; background: white; font-family: inherit; cursor: pointer;">
                    <option value="">— Semua Kelompok —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <!-- Tabs -->
        <div style="display: flex; gap: 0.5rem; border-bottom: 1.5px solid var(--neutral-200); margin-bottom: 1rem; flex-shrink: 0;">
            <button onclick="switchDetailTab('present')" id="tabBtnPresent" style="padding: 0.5rem 1rem; border: none; border-bottom: 3px solid var(--primary); background: none; font-weight: 700; font-size: 0.85rem; color: var(--primary); cursor: pointer; transition: all 0.2s;">
                ✅ Hadir (<span id="tabCountPresent">0</span>)
            </button>
            <button onclick="switchDetailTab('absent')" id="tabBtnAbsent" style="padding: 0.5rem 1rem; border: none; border-bottom: 3px solid transparent; background: none; font-weight: 700; font-size: 0.85rem; color: var(--neutral-500); cursor: pointer; transition: all 0.2s;">
                ❌ Belum Hadir (<span id="tabCountAbsent">0</span>)
            </button>
        </div>
        
        <!-- Content List Container -->
        <div style="flex: 1; overflow-y: auto; min-height: 200px; padding: 2px;">
            <!-- Present List -->
            <div id="detailPresentList" style="display: flex; flex-direction: column; gap: 0.4rem;">
                <!-- Dynamically loaded -->
            </div>
            
            <!-- Absent List -->
            <div id="detailAbsentList" style="display: none; flex-direction: column; gap: 0.4rem;">
                <!-- Dynamically loaded -->
            </div>
        </div>
        
        <!-- Footer -->
        <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--neutral-150); padding-top: 1rem; margin-top: 1rem; flex-shrink: 0;">
            <button type="button" class="btn btn-outline" onclick="closeSessionDetailModal()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const SESSION_ID = {{ $activeSession?->id ?? 'null' }};

// ── Fetch & update stats ──────────────────────────────────────────────────────
async function fetchStats() {
    const url = SESSION_ID
        ? `/api/dashboard/stats?session_id=${SESSION_ID}`
        : '/api/dashboard/stats';
    try {
        const res  = await fetch(url);
        const data = await res.json();
        if (!data.success) return;

        // Update header stats
        document.getElementById('totalParticipants').textContent = data.total_participants;
        document.getElementById('totalPresent').textContent = data.total_present;
        document.getElementById('totalAbsent').textContent  = data.total_absent;
        document.getElementById('percentage').textContent   = data.percentage + '%';
        
        // Update gender stats
        document.getElementById('totalMale').textContent = data.total_male;
        document.getElementById('totalFemale').textContent = data.total_female;

        // Update group cards
        data.groups.forEach(g => {
            const el = document.getElementById(`group-pct-${g.id}`);
            const bar = document.getElementById(`group-bar-${g.id}`);
            const pEl = document.getElementById(`group-present-${g.id}`);
            const aEl = document.getElementById(`group-absent-${g.id}`);
            if (el)  el.textContent = g.percentage + '%';
            if (bar) bar.style.width = g.percentage + '%';
            if (pEl) pEl.textContent = g.present;
            if (aEl) aEl.textContent = g.absent;
        });

        // Update live feed
        const list = document.getElementById('liveList');
        if (data.recent_attendances.length > 0 && list.querySelector('[data-placeholder]')) {
            list.innerHTML = '';
        }
        if (list.children.length === 0) {
            data.recent_attendances.forEach(a => appendFeedItem(a, false));
        }

        // Session progress
        updateSessionProgress(data.session);

    } catch(e) { console.warn('Stats fetch error:', e); }
}

// ── Session progress bar ──────────────────────────────────────────────────────
function updateSessionProgress(session) {
    if (!session) return;
    const now    = new Date();
    const start  = new Date(now.toDateString() + ' ' + session.start_time);
    const end    = new Date(now.toDateString() + ' ' + session.end_time);
    const total  = end - start;
    const elapsed = now - start;
    const pct    = Math.min(100, Math.max(0, (elapsed / total) * 100));

    const bar = document.getElementById('sessionProgress');
    if (bar) bar.style.width = pct.toFixed(1) + '%';

    const remaining = Math.max(0, Math.floor((end - now) / 60000));
    const el = document.getElementById('sessionTimeLeft');
    if (el) el.textContent = remaining > 0 ? `Sisa ${remaining} menit` : 'Sesi berakhir';
}

// ── Append feed item ──────────────────────────────────────────────────────────
function appendFeedItem(a, isNew = true) {
    const list = document.getElementById('liveList');
    const placeholder = list.querySelector('[data-placeholder]');
    if (placeholder) placeholder.remove();

    const initials = a.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const color = a.group_color || '#0052cc';

    const item = document.createElement('div');
    item.className = 'live-item' + (isNew ? ' new-entry' : '');
    item.innerHTML = `
        <div class="live-avatar" style="background:${color}">${initials}</div>
        <div class="live-info">
            <div class="live-name">${a.name}</div>
            <div class="live-group">${a.group}</div>
        </div>
        <div class="live-time">${a.time || new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}</div>
    `;
    list.insertBefore(item, list.firstChild);
    setTimeout(() => item.classList.remove('new-entry'), 3000);

    while (list.children.length > 50) list.removeChild(list.lastChild);
}

// ── WebSocket (Reverb) ────────────────────────────────────────────────────────
if (typeof window.Echo !== 'undefined') {
    window.Echo.channel('attendance')
        .listen('.attendance.recorded', (e) => {
            // Update stats counters immediately
            const pEl = document.getElementById('totalPresent');
            const aEl = document.getElementById('totalAbsent');
            if (pEl) pEl.textContent = parseInt(pEl.textContent || 0) + 1;
            if (aEl) aEl.textContent = Math.max(0, parseInt(aEl.textContent || 0) - 1);

            // Update group card
            const gPresent = document.getElementById(`group-present-${e.group_id}`);
            if (gPresent) gPresent.textContent = parseInt(gPresent.textContent || 0) + 1;

            // Add to feed
            appendFeedItem({ name: e.participant_name, group: e.group_name, group_color: e.group_color });

            // Show toast
            showToast(`${e.participant_name} dari ${e.group_name} hadir`, 'success');

            // Refresh full stats after 1s
            setTimeout(fetchStats, 1000);
        });
}

// ── Init ──────────────────────────────────────────────────────────────────────
fetchStats();
setInterval(fetchStats, 15000); // Refresh every 15s as fallback
setInterval(() => {
    if (SESSION_ID) updateSessionProgress({ start_time: '{{ $activeSession?->start_time }}', end_time: '{{ $activeSession?->end_time }}' });
}, 10000);

// ── Session Detail Modal Handlers ─────────────────────────────────────────────
let currentDetailSessionId = null;
let currentDetailTab = 'present';
let rawSessionData = null;

async function openSessionDetailModal(sessionId, sessionName) {
    currentDetailSessionId = sessionId;
    currentDetailTab = 'present';
    document.getElementById('detailModalTitle').textContent = `📅 Detail Absensi Sesi: ${sessionName}`;
    document.getElementById('detailGroupFilter').value = '';
    
    switchDetailTab('present');
    document.getElementById('sessionDetailModal').style.display = 'flex';
    
    await fetchSessionDetail();
}

function closeSessionDetailModal() {
    document.getElementById('sessionDetailModal').style.display = 'none';
}

async function fetchSessionDetail() {
    if (!currentDetailSessionId) return;
    
    const groupId = document.getElementById('detailGroupFilter').value;
    const url = `/api/dashboard/sessions/${currentDetailSessionId}/detail?group_id=${groupId}`;
    
    try {
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            rawSessionData = data;
            renderSessionDetailLists();
        }
    } catch(e) {
        console.error("Failed to fetch session details:", e);
    }
}

function filterSessionDetail() {
    fetchSessionDetail();
}

function switchDetailTab(tab) {
    currentDetailTab = tab;
    
    const btnPresent = document.getElementById('tabBtnPresent');
    const btnAbsent = document.getElementById('tabBtnAbsent');
    const listPresent = document.getElementById('detailPresentList');
    const listAbsent = document.getElementById('detailAbsentList');
    
    if (tab === 'present') {
        btnPresent.style.color = 'var(--primary)';
        btnPresent.style.borderBottomColor = 'var(--primary)';
        btnAbsent.style.color = 'var(--neutral-500)';
        btnAbsent.style.borderBottomColor = 'transparent';
        
        listPresent.style.display = 'flex';
        listAbsent.style.display = 'none';
    } else {
        btnAbsent.style.color = 'var(--primary)';
        btnAbsent.style.borderBottomColor = 'var(--primary)';
        btnPresent.style.color = 'var(--neutral-500)';
        btnPresent.style.borderBottomColor = 'transparent';
        
        listPresent.style.display = 'none';
        listAbsent.style.display = 'flex';
    }
}

function renderSessionDetailLists() {
    if (!rawSessionData) return;
    
    document.getElementById('detailTotalCount').textContent = rawSessionData.stats.total;
    document.getElementById('detailPresentCount').textContent = rawSessionData.stats.present;
    document.getElementById('detailAbsentCount').textContent = rawSessionData.stats.absent;
    
    document.getElementById('tabCountPresent').textContent = rawSessionData.stats.present;
    document.getElementById('tabCountAbsent').textContent = rawSessionData.stats.absent;
    
    const presentContainer = document.getElementById('detailPresentList');
    presentContainer.innerHTML = '';
    
    if (rawSessionData.present.length > 0) {
        rawSessionData.present.forEach(p => {
            const initials = p.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
            const color = p.group_color || '#0052cc';
            const item = document.createElement('div');
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.justifyContent = 'space-between';
            item.style.padding = '0.55rem 0.75rem';
            item.style.border = '1px solid var(--neutral-200)';
            item.style.borderRadius = '6px';
            item.style.background = 'white';
            
            item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: ${color}; color: white; font-size: 0.75rem; font-weight: 700;">${initials}</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--neutral-900);">${p.name} <span style="font-size: 0.75rem; color: var(--neutral-500);">(${p.gender === 'Laki-laki' ? 'L' : 'P'})</span></div>
                        <div style="font-size: 0.7rem; color: var(--neutral-500); margin-top: 1px;">Kelompok: ${p.group_name}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--success);">🕒 ${p.check_in_time}</div>
                    <div style="font-size: 0.65rem; color: var(--neutral-400); margin-top: 1px;">Metode: <span class="badge" style="background:var(--neutral-100);color:var(--neutral-600);border:1px solid var(--neutral-200);font-size:0.55rem;padding:0px 4px;">${p.method.toUpperCase()}</span></div>
                </div>
            `;
            presentContainer.appendChild(item);
        });
    } else {
        presentContainer.innerHTML = '<div style="color:var(--neutral-400);text-align:center;font-size:0.8rem;padding:1.5rem;font-style:italic;">Tidak ada peserta hadir untuk filter ini.</div>';
    }
    
    const absentContainer = document.getElementById('detailAbsentList');
    absentContainer.innerHTML = '';
    
    if (rawSessionData.absent.length > 0) {
        rawSessionData.absent.forEach(p => {
            const initials = p.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
            const color = p.group_color || '#0052cc';
            const item = document.createElement('div');
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.justifyContent = 'space-between';
            item.style.padding = '0.55rem 0.75rem';
            item.style.border = '1px solid var(--neutral-200)';
            item.style.borderRadius = '6px';
            item.style.background = 'white';
            
            const phoneStr = p.phone ? `<a href="https://wa.me/${p.phone}" target="_blank" style="text-decoration:none;font-size:0.65rem;color:var(--primary);font-weight:600;display:inline-flex;align-items:center;gap:2px;margin-top:1px;">💬 Hubungi WA</a>` : '';
            
            item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: ${color}; color: white; font-size: 0.75rem; font-weight: 700;">${initials}</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--neutral-900);">${p.name} <span style="font-size: 0.75rem; color: var(--neutral-500);">(${p.gender === 'Laki-laki' ? 'L' : 'P'})</span></div>
                        <div style="font-size: 0.7rem; color: var(--neutral-500); margin-top: 1px;">Kelompok: ${p.group_name}</div>
                    </div>
                </div>
                <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end;">
                    <span style="font-size:0.75rem;font-weight:600;color:var(--danger);background:var(--danger-lt);padding:2px 6px;border-radius:4px;">Belum Hadir</span>
                    ${phoneStr}
                </div>
            `;
            absentContainer.appendChild(item);
        });
    } else {
        absentContainer.innerHTML = '<div style="color:var(--neutral-400);text-align:center;font-size:0.8rem;padding:1.5rem;font-style:italic;">Semua peserta telah hadir! 🎉</div>';
    }
}
</script>
@endpush
