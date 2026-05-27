@extends('layouts.admin')

@section('title', 'Reset Progress — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.maintenance.reset-all-campaigns') }}" class="text-sm underline">&larr; Reset All Campaigns</a>
    <h1 class="section-title mt-4">
        @if($progress['dry_run'])
            Dry run results
        @else
            Resetting all campaigns…
        @endif
    </h1>
    <p class="mt-2 text-sm text-archive-gray break-all">Session: {{ $sessionId }}</p>
</div>

<div
    id="reset-root"
    class="max-w-3xl border border-archive-border bg-white p-6"
    data-tick-url="{{ route('admin.maintenance.reset-all-campaigns.tick', $sessionId) }}"
    data-status-url="{{ route('admin.maintenance.reset-all-campaigns.status', $sessionId) }}"
    data-pause-url="{{ route('admin.maintenance.reset-all-campaigns.pause', $sessionId) }}"
    data-resume-url="{{ route('admin.maintenance.reset-all-campaigns.resume', $sessionId) }}"
    data-auto-start="{{ ($progress['dry_run'] || $progress['completed']) ? '0' : '1' }}"
    data-is-dry-run="{{ $progress['dry_run'] ? '1' : '0' }}"
>
    @if($progress['dry_run'])
        <div class="mb-6 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Dry run only — no campaigns or files were deleted. Use the destructive form on the previous page when ready.
        </div>
    @else
        <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <strong>Keep this page open.</strong> Deletion runs in small steps (one campaign per request) to avoid timeouts.
        </div>
    @endif

    <div id="reset-error" class="mb-4 hidden border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"></div>

    <div class="mb-6">
        <div class="mb-2 flex justify-between text-sm">
            <span id="reset-phase-label">{{ ucfirst(str_replace('_', ' ', $progress['phase'])) }}</span>
            <span id="reset-percent">{{ $progress['percent'] }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden bg-archive-light">
            <div id="reset-bar" class="h-full bg-archive-black transition-all duration-300" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
        <div>
            <dt class="text-archive-gray">Campaigns (total)</dt>
            <dd id="count-campaigns" class="text-lg font-medium">{{ number_format($progress['counts']['campaigns'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Deleted</dt>
            <dd id="proc-campaigns" class="text-lg font-medium text-red-700">{{ $progress['processed']['campaigns'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Storage files</dt>
            <dd id="proc-storage" class="text-lg font-medium">{{ $progress['processed']['storage_files'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Last campaign ID</dt>
            <dd id="last-campaign-id" class="text-lg font-medium font-mono">{{ $progress['last_campaign_id'] ?? '—' }}</dd>
        </div>
    </dl>

    <dl class="mt-4 grid grid-cols-2 gap-4 border border-archive-border bg-archive-light p-4 text-xs sm:grid-cols-3">
        <div>
            <dt class="text-archive-gray">Assets (before)</dt>
            <dd>{{ number_format($progress['counts']['assets'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Videos (before)</dt>
            <dd>{{ number_format($progress['counts']['videos'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Bookmarks (before)</dt>
            <dd>{{ number_format($progress['counts']['bookmarks'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Watchers (before)</dt>
            <dd>{{ number_format($progress['counts']['watchers'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Revisions (before)</dt>
            <dd>{{ number_format($progress['counts']['revisions'] ?? 0) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Media files (before)</dt>
            <dd>{{ number_format($progress['counts']['media_files'] ?? 0) }}</dd>
        </div>
    </dl>

    <p class="mt-4 text-xs text-archive-gray">
        Last action: <span id="last-action" class="font-mono">{{ $progress['last_action'] ?? '—' }}</span>
    </p>

    @if(! $progress['dry_run'])
        <div class="mt-6 flex flex-wrap gap-3">
            <button type="button" id="btn-tick" class="btn-primary text-xs">Process next step</button>
            <button type="button" id="btn-pause" class="btn-outline text-xs" @if($progress['completed'] || $progress['paused']) disabled @endif>Pause</button>
            <button type="button" id="btn-resume" class="btn-primary text-xs {{ $progress['paused'] ? '' : 'hidden' }}">Resume</button>
        </div>
    @endif

    <div id="reset-done" class="mt-8 border border-green-200 bg-green-50 p-6 {{ $progress['completed'] ? '' : 'hidden' }}">
        <h2 class="font-display text-lg text-green-900">Reset complete</h2>
        <p class="mt-2 text-sm text-green-900">
            All campaigns and related media have been removed. Import queue and archive caches were cleared.
        </p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('admin.check-new-campaigns.index') }}" class="btn-primary text-xs">
                Start Fresh Iraq Import
            </a>
            <a href="{{ route('admin.maintenance.reset-all-campaigns') }}" class="btn-outline text-xs">Back to maintenance</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.getElementById('reset-root');
    if (!root) return;

    const isDryRun = root.dataset.isDryRun === '1';
    const autoStart = root.dataset.autoStart === '1';
    const urls = {
        tick: root.dataset.tickUrl,
        status: root.dataset.statusUrl,
        pause: root.dataset.pauseUrl,
        resume: root.dataset.resumeUrl,
    };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const els = {
        bar: document.getElementById('reset-bar'),
        percent: document.getElementById('reset-percent'),
        phase: document.getElementById('reset-phase-label'),
        procCampaigns: document.getElementById('proc-campaigns'),
        procStorage: document.getElementById('proc-storage'),
        lastCampaignId: document.getElementById('last-campaign-id'),
        lastAction: document.getElementById('last-action'),
        error: document.getElementById('reset-error'),
        done: document.getElementById('reset-done'),
        btnTick: document.getElementById('btn-tick'),
        btnPause: document.getElementById('btn-pause'),
        btnResume: document.getElementById('btn-resume'),
    };

    let running = false;
    let paused = false;
    let completed = {{ $progress['completed'] ? 'true' : 'false' }};
    let timer = null;

    function setText(el, value) {
        if (el) el.textContent = value ?? '—';
    }

    function showError(msg) {
        if (!els.error) return;
        els.error.textContent = msg;
        els.error.classList.remove('hidden');
    }

    function clearError() {
        if (els.error) {
            els.error.textContent = '';
            els.error.classList.add('hidden');
        }
    }

    async function fetchJson(url, method) {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const text = await res.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (e) {
            throw new Error('Invalid JSON (HTTP ' + res.status + ')');
        }

        if (!res.ok) {
            throw new Error(data?.error || data?.message || ('HTTP ' + res.status));
        }

        return data;
    }

    function update(p) {
        if (!p) return;

        if (els.bar) els.bar.style.width = (p.percent || 0) + '%';
        setText(els.percent, (p.percent || 0) + '%');
        setText(els.phase, (p.phase || '').replace(/_/g, ' '));
        setText(els.procCampaigns, p.processed?.campaigns ?? 0);
        setText(els.procStorage, p.processed?.storage_files ?? 0);
        setText(els.lastCampaignId, p.last_campaign_id ?? '—');
        setText(els.lastAction, p.last_action ?? '—');

        paused = !!p.paused;
        completed = !!p.completed;

        if (completed) {
            if (els.done) els.done.classList.remove('hidden');
            stop();
        }

        if (els.btnPause) els.btnPause.disabled = completed || paused;
        if (els.btnResume) {
            els.btnResume.classList.toggle('hidden', !paused);
            els.btnResume.disabled = completed;
        }
        if (els.btnTick) els.btnTick.disabled = completed;
    }

    function stop() {
        running = false;
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    function schedule() {
        if (timer) clearTimeout(timer);
        if (!running || paused || completed || isDryRun) return;
        timer = setTimeout(tick, 400);
    }

    async function tick() {
        if (completed || isDryRun) return;

        try {
            const data = await fetchJson(urls.tick, 'POST');
            clearError();
            if (data?.progress) update(data.progress);
            if (!completed && !paused) schedule();
        } catch (e) {
            showError(e.message || String(e));
            stop();
        }
    }

    if (els.btnTick) {
        els.btnTick.addEventListener('click', function () {
            running = true;
            tick();
        });
    }

    if (els.btnPause) {
        els.btnPause.addEventListener('click', async function () {
            try {
                const data = await fetchJson(urls.pause, 'POST');
                if (data?.progress) update(data.progress);
                stop();
            } catch (e) {
                showError(e.message);
            }
        });
    }

    if (els.btnResume) {
        els.btnResume.addEventListener('click', async function () {
            try {
                const data = await fetchJson(urls.resume, 'POST');
                if (data?.progress) update(data.progress);
                running = true;
                paused = false;
                schedule();
            } catch (e) {
                showError(e.message);
            }
        });
    }

    if (autoStart && !isDryRun && !completed) {
        running = true;
        schedule();
    }
})();
</script>
@endpush
