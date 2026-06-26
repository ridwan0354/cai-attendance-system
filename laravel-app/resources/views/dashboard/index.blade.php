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
                <span class="card-title">📍 Status Per Grup Regional</span>
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
</script>
@endpush
