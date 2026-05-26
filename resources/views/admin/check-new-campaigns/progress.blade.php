@extends('layouts.admin')

@section('title', 'New Campaigns Progress — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.check-new-campaigns.index') }}" class="text-sm underline">&larr; Check New Campaigns</a>
    <h1 class="section-title mt-4">Check for New Iraq Campaigns</h1>
    <p class="mt-2 text-sm text-archive-gray break-all">Run ID: {{ $batch->id }}</p>
</div>

<div
    id="checker-root"
    class="max-w-3xl border border-archive-border bg-white p-6"
    data-status-url="{{ route('admin.check-new-campaigns.status', $batch) }}"
    data-process-url="{{ route('admin.check-new-campaigns.process', $batch) }}"
    data-pause-url="{{ route('admin.check-new-campaigns.pause', $batch) }}"
    data-resume-url="{{ route('admin.check-new-campaigns.resume', $batch) }}"
    data-delay-min="{{ min(config('import.bulk_process_delay_ms', 3000), config('import.bulk_process_delay_max_ms', 5000)) }}"
    data-delay-max="{{ max(config('import.bulk_process_delay_ms', 3000), config('import.bulk_process_delay_max_ms', 5000)) }}"
    data-auto-start="{{ ($progress['can_auto_process'] ?? true) ? '1' : '0' }}"
>
    <div class="mb-6 border border-archive-border bg-archive-light px-5 py-4">
        <p class="text-sm font-semibold">Keep this page open while the import runs.</p>
        <p class="mt-2 text-sm text-archive-gray">
            This tool scans newest pages first and imports only new campaigns. It stops automatically after
            <strong>{{ $progress['stop_after_existing'] }}</strong> consecutive existing campaigns.
        </p>
    </div>

    <div id="checker-error" class="mb-4 hidden border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"></div>

    <div class="mb-6">
        <div class="mb-2 flex justify-between text-sm">
            <span id="progress-label">
                {{ $progress['completed'] ? 'Complete' : ($progress['paused'] ? 'Paused' : 'Checking…') }}
            </span>
            <span id="progress-percent">{{ $progress['percent'] }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden bg-archive-light">
            <div id="progress-bar" class="h-full bg-archive-black transition-all duration-300" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-6">
        <div>
            <dt class="text-archive-gray">Imported</dt>
            <dd id="stat-imported" class="text-lg font-medium text-green-700">{{ $progress['imported'] }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Existing</dt>
            <dd id="stat-existing" class="text-lg font-medium">{{ $progress['existing_skipped'] ?? 0 }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Failed</dt>
            <dd id="stat-failed" class="text-lg font-medium text-red-600">{{ $progress['failed'] }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Skipped</dt>
            <dd id="stat-skipped" class="text-lg font-medium text-amber-700">{{ $progress['skipped'] }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Pending</dt>
            <dd id="stat-pending" class="text-lg font-medium">{{ $progress['pending'] }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">New found</dt>
            <dd id="stat-new-found" class="text-lg font-medium">{{ $progress['total'] }}</dd>
        </div>
    </dl>

    <div class="mt-4 grid gap-3 border border-archive-border bg-archive-light p-3 text-xs sm:grid-cols-2">
        <div>
            <p class="text-archive-gray">Current URL</p>
            <p id="meta-current-url" class="mt-1 break-all font-mono text-archive-black">{{ $progress['current_url'] ?? '—' }}</p>
        </div>
        <div>
            <p class="text-archive-gray">Current page</p>
            <p id="meta-page" class="mt-1 font-mono text-archive-black">{{ $progress['crawl_next_page'] ?? 1 }} / {{ $progress['crawl_max_page'] ?? 1 }}</p>
        </div>
        <div>
            <p class="text-archive-gray">Existing streak</p>
            <p id="meta-streak" class="mt-1 font-mono text-archive-black">{{ $progress['consecutive_existing'] ?? 0 }} / {{ $progress['stop_after_existing'] ?? 20 }}</p>
        </div>
        <div>
            <p class="text-archive-gray">State</p>
            <p id="meta-status" class="mt-1 font-mono text-archive-black">{{ $progress['status'] }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="button" id="btn-pause" class="btn-outline text-xs" @if($progress['completed'] || $progress['paused']) disabled @endif>Pause</button>
        <button type="button" id="btn-resume" class="btn-primary text-xs {{ $progress['paused'] ? '' : 'hidden' }}" @if($progress['completed']) disabled @endif>Resume</button>
    </div>

    <div id="log" class="mt-6 max-h-56 overflow-y-auto border border-archive-border bg-archive-light p-3 font-mono text-xs text-archive-gray"></div>

    <div id="done-actions" class="mt-8 {{ $progress['completed'] ? '' : 'hidden' }}">
        <p class="mb-4 text-sm text-green-700">Finished checking. New campaigns imported.</p>
        <a href="{{ route('admin.check-new-campaigns.index') }}" class="btn-primary text-xs">Back</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('checker-root');
    if (!root) return;

    const urls = {
        status: root.dataset.statusUrl,
        process: root.dataset.processUrl,
        pause: root.dataset.pauseUrl,
        resume: root.dataset.resumeUrl,
    };

    const delayMin = parseInt(root.dataset.delayMin, 10) || 3000;
    const delayMax = parseInt(root.dataset.delayMax, 10) || 5000;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const els = {
        error: document.getElementById('checker-error'),
        log: document.getElementById('log'),
        bar: document.getElementById('progress-bar'),
        percent: document.getElementById('progress-percent'),
        label: document.getElementById('progress-label'),
        imported: document.getElementById('stat-imported'),
        existing: document.getElementById('stat-existing'),
        failed: document.getElementById('stat-failed'),
        skipped: document.getElementById('stat-skipped'),
        pending: document.getElementById('stat-pending'),
        newFound: document.getElementById('stat-new-found'),
        currentUrl: document.getElementById('meta-current-url'),
        page: document.getElementById('meta-page'),
        streak: document.getElementById('meta-streak'),
        status: document.getElementById('meta-status'),
        btnPause: document.getElementById('btn-pause'),
        btnResume: document.getElementById('btn-resume'),
        done: document.getElementById('done-actions'),
    };

    let running = false;
    let paused = {{ $progress['paused'] ? 'true' : 'false' }};
    let completed = {{ $progress['completed'] ? 'true' : 'false' }};
    let timer = null;

    let consecutiveFailures = 0;
    const MAX_RETRIES = 3;
    const RETRY_WAIT_MS = 10000;

    function setText(el, value) { if (el) el.textContent = value; }
    function showError(msg) {
        if (els.error) { els.error.textContent = msg; els.error.classList.remove('hidden'); }
        log('ERROR: ' + msg);
    }
    function clearError() { if (els.error) { els.error.textContent = ''; els.error.classList.add('hidden'); } }
    function log(msg) {
        if (!els.log) return;
        const line = document.createElement('div');
        line.textContent = new Date().toLocaleTimeString() + ' — ' + msg;
        els.log.prepend(line);
    }
    function randDelay() { return delayMin + Math.floor(Math.random() * (delayMax - delayMin + 1)); }

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
        try { data = text ? JSON.parse(text) : null; } catch (e) {
            throw new Error('Invalid JSON (HTTP ' + res.status + '): ' + text.substring(0, 200));
        }
        if (!res.ok) throw new Error(data?.error || data?.message || ('HTTP ' + res.status));
        return data;
    }

    function update(p) {
        if (!p) return;
        if (els.bar) els.bar.style.width = p.percent + '%';
        setText(els.percent, p.percent + '%');
        setText(els.imported, p.imported);
        setText(els.existing, p.existing_skipped || 0);
        setText(els.failed, p.failed);
        setText(els.skipped, p.skipped);
        setText(els.pending, p.pending);
        setText(els.newFound, p.total);
        setText(els.currentUrl, p.current_url || p.next_pending_url || '—');
        setText(els.page, (p.crawl_next_page || 1) + ' / ' + (p.crawl_max_page || 1));
        setText(els.streak, (p.consecutive_existing || 0) + ' / ' + (p.stop_after_existing || 20));
        setText(els.status, p.status || '—');

        paused = !!p.paused;
        completed = !!p.completed;

        if (completed) {
            setText(els.label, 'Complete');
            stop();
            if (els.done) els.done.classList.remove('hidden');
        } else if (paused) {
            setText(els.label, 'Paused');
            stop();
        } else if (running) {
            setText(els.label, 'Checking…');
        } else {
            setText(els.label, 'Ready');
        }

        if (els.btnPause) els.btnPause.disabled = completed || paused;
        if (els.btnResume) {
            els.btnResume.classList.toggle('hidden', !paused);
            els.btnResume.disabled = completed;
        }
    }

    function schedule(ms) {
        if (timer) clearTimeout(timer);
        if (!running || paused || completed) return;
        timer = setTimeout(step, ms);
    }

    function stop() {
        running = false;
        if (timer) { clearTimeout(timer); timer = null; }
    }

    async function step() {
        if (!running || paused || completed) return;
        try {
            const data = await fetchJson(urls.process, 'POST');
            consecutiveFailures = 0;
            clearError();
            if (data?.progress) update(data.progress);

            (data?.results || []).forEach(function (r) {
                if (!r?.item?.url) return;
                const u = r.item.url;
                if (r.status === 'done') log('✓ imported ' + u);
                else if (r.status === 'skipped') log('↷ duplicate ' + u);
                else if (r.status === 'failed') log('✗ failed ' + u + (r.message ? ' — ' + r.message : ''));
            });

            if (data?.crawl?.page) {
                log('Scanned page ' + data.crawl.page + ' (new: ' + (data.crawl.enqueued || 0) + ', existing: ' + (data.crawl.existing || 0) + ')');
            }

            schedule(randDelay());
        } catch (e) {
            consecutiveFailures++;
            showError(e.message || String(e));
            if (consecutiveFailures >= MAX_RETRIES) {
                log('Too many errors — pausing.');
                try {
                    await fetchJson(urls.pause, 'POST');
                    const st = await fetchJson(urls.status, 'GET');
                    if (st) update(st);
                } catch (pauseErr) {
                    showError('Could not pause: ' + (pauseErr.message || String(pauseErr)));
                }
                paused = true;
                stop();
                return;
            }
            log('Retry ' + consecutiveFailures + '/' + MAX_RETRIES + ' in 10s…');
            schedule(RETRY_WAIT_MS);
        }
    }

    function start() {
        if (running || paused || completed) return;
        running = true;
        clearError();
        log('Started.');
        step();
    }

    if (els.btnPause) {
        els.btnPause.addEventListener('click', function () {
            stop();
            fetchJson(urls.pause, 'POST')
                .then(function (data) { paused = true; if (data?.progress) update(data.progress); log('Paused.'); })
                .catch(function (e) { showError(e.message || String(e)); });
        });
    }

    if (els.btnResume) {
        els.btnResume.addEventListener('click', function () {
            fetchJson(urls.resume, 'POST')
                .then(function (data) { paused = false; if (data?.progress) update(data.progress); log('Resumed.'); start(); })
                .catch(function (e) { showError(e.message || String(e)); });
        });
    }

    if (root.dataset.autoStart === '1' && !paused && !completed) start();
    if (paused) log('Paused. Click Resume to continue.');
    if (completed) log('Already completed.');
});
</script>
@endpush

