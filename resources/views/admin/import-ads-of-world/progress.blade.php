@extends('layouts.admin')

@section('title', 'Import Progress — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.import.queue') }}" class="text-sm underline">&larr; Bulk import</a>
    <h1 class="section-title mt-4">Import Progress</h1>
    <p class="mt-2 text-sm text-archive-gray break-all">Batch: {{ $batch->id }}</p>
</div>

<div
    id="import-progress-root"
    class="max-w-3xl border border-archive-border bg-white p-6"
    data-status-url="{{ route('admin.import-ads-of-world.status', $batch) }}"
    data-process-url="{{ route('admin.import-ads-of-world.process', $batch) }}"
    data-pause-url="{{ route('admin.import-ads-of-world.pause', $batch) }}"
    data-resume-url="{{ route('admin.import-ads-of-world.resume', $batch) }}"
    data-retry-url="{{ route('admin.import-ads-of-world.retry-failed', $batch) }}"
    data-delay-min="{{ min(config('import.bulk_process_delay_ms', 3000), config('import.bulk_process_delay_max_ms', 5000)) }}"
    data-delay-max="{{ max(config('import.bulk_process_delay_ms', 3000), config('import.bulk_process_delay_max_ms', 5000)) }}"
    data-auto-start="{{ ($progress['can_auto_process'] ?? false) ? '1' : '0' }}"
    data-batch-status="{{ $progress['status'] }}"
>
    <div class="mb-6 border border-blue-200 bg-blue-50 px-5 py-4">
        <p class="text-sm font-semibold text-blue-900">Keep this page open while the import runs</p>
        <p class="mt-2 text-sm text-blue-900">
            Each campaign is imported one at a time in your browser — safe for cPanel hosting.
            You can pause anytime and resume later. Do not close this tab until finished or paused.
        </p>
    </div>

    <div id="import-error" class="mb-4 hidden border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"></div>

    <div class="mb-6">
        <div class="mb-2 flex justify-between text-sm">
            <span id="progress-label">{{ $progress['completed'] ? 'Complete' : ($progress['paused'] ? 'Paused' : 'Importing…') }}</span>
            <span id="progress-percent">{{ $progress['percent'] }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden bg-archive-light">
            <div id="progress-bar" class="h-full bg-archive-black transition-all duration-300" style="width: {{ $progress['percent'] }}%"></div>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-5">
        <div>
            <dt class="text-archive-gray">Imported</dt>
            <dd id="stat-imported" class="text-lg font-medium text-green-700">{{ $progress['imported'] }}</dd>
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
            <dt class="text-archive-gray">Total</dt>
            <dd id="stat-total" class="text-lg font-medium">{{ $progress['total'] }}</dd>
        </div>
    </dl>

    <div class="mt-4 border border-archive-border bg-archive-light p-3 text-xs">
        <p class="text-archive-gray">Current URL</p>
        <p id="meta-current-url" class="mt-1 break-all font-mono text-archive-black">{{ $progress['current_url'] ?? '—' }}</p>
    </div>

    <p id="progress-detail" class="mt-3 text-xs text-archive-gray">
        Processed: <span id="stat-processed">{{ $progress['processed'] }}</span> / <span id="stat-total-2">{{ $progress['total'] }}</span>
        — Queue: {{ $progress['queue_order_label'] ?? 'Oldest first' }}
    </p>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="button" id="btn-pause" class="btn-outline text-xs" @if($progress['completed'] || $progress['paused']) disabled @endif>Pause</button>
        <button type="button" id="btn-resume" class="btn-primary text-xs {{ $progress['paused'] ? '' : 'hidden' }}" @if($progress['completed']) disabled @endif>Resume</button>
        <button type="button" id="btn-retry-failed" class="btn-outline text-xs" @if($progress['failed'] < 1 || $progress['completed']) disabled @endif>Retry failed</button>
    </div>

    <div id="log" class="mt-6 max-h-48 overflow-y-auto border border-archive-border bg-archive-light p-3 font-mono text-xs text-archive-gray"></div>

    <div id="done-actions" class="mt-8 {{ $progress['completed'] ? '' : 'hidden' }}">
        <p class="mb-4 text-sm text-green-700">Bulk import finished.</p>
        <a href="{{ route('admin.import.queue') }}" class="btn-primary text-xs">Back to bulk import</a>
        <a href="{{ route('admin.campaigns.index') }}" class="btn-outline ml-3 text-xs">View campaigns</a>
    </div>

    <div class="mt-8 border-t border-archive-border pt-6">
        <p class="section-label mb-2 text-red-600">Danger zone</p>
        <p class="mb-3 text-xs text-archive-gray">Remove the most recent bulk import batch and all its campaigns and media.</p>
        <form method="POST" action="{{ route('admin.import.delete-last') }}" onsubmit="return confirm('Delete the last Iraq bulk import and all its media? This cannot be undone.');">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn-outline text-xs text-red-600">Delete Last Import</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('import-progress-root');
    if (!root) return;

    const urls = {
        status: root.dataset.statusUrl,
        process: root.dataset.processUrl,
        pause: root.dataset.pauseUrl,
        resume: root.dataset.resumeUrl,
        retry: root.dataset.retryUrl,
    };
    const delayMin = parseInt(root.dataset.delayMin, 10) || 2000;
    const delayMax = parseInt(root.dataset.delayMax, 10) || 5000;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const els = {
        error: document.getElementById('import-error'),
        log: document.getElementById('log'),
        bar: document.getElementById('progress-bar'),
        percent: document.getElementById('progress-percent'),
        label: document.getElementById('progress-label'),
        detail: document.getElementById('progress-detail'),
        total: document.getElementById('stat-total'),
        imported: document.getElementById('stat-imported'),
        failed: document.getElementById('stat-failed'),
        skipped: document.getElementById('stat-skipped'),
        pending: document.getElementById('stat-pending'),
        processed: document.getElementById('stat-processed'),
        total2: document.getElementById('stat-total-2'),
        done: document.getElementById('done-actions'),
        btnPause: document.getElementById('btn-pause'),
        btnResume: document.getElementById('btn-resume'),
        btnRetry: document.getElementById('btn-retry-failed'),
        metaCurrentUrl: document.getElementById('meta-current-url'),
    };

    let running = false;
    let loopTimer = null;
    let consecutiveFailures = 0;
    const MAX_RETRIES = 3;
    const RETRY_WAIT_MS = 10000;
    let state = {
        completed: {{ $progress['completed'] ? 'true' : 'false' }},
        paused: {{ $progress['paused'] ? 'true' : 'false' }},
    };

    function setText(el, value) {
        if (el) el.textContent = value;
    }

    function log(msg) {
        if (!els.log) return;
        const line = document.createElement('div');
        line.textContent = new Date().toLocaleTimeString() + ' — ' + msg;
        els.log.prepend(line);
    }

    function showError(msg) {
        if (els.error) {
            els.error.textContent = msg;
            els.error.classList.remove('hidden');
        }
        log('ERROR: ' + msg);
    }

    function clearError() {
        if (els.error) {
            els.error.textContent = '';
            els.error.classList.add('hidden');
        }
    }

    function randomDelay() {
        return delayMin + Math.floor(Math.random() * (delayMax - delayMin + 1));
    }

    function wait(ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    }

    function updateButtons(p) {
        if (!p) return;
        if (els.btnPause) els.btnPause.disabled = p.completed || p.paused;
        if (els.btnResume) {
            const showResume = p.paused || (p.can_process && p.status === 'queued');
            els.btnResume.classList.toggle('hidden', !showResume);
            els.btnResume.disabled = p.completed;
        }
        if (els.btnRetry) els.btnRetry.disabled = p.completed || p.failed < 1;
    }

    function updateProgress(p) {
        if (!p) return;

        if (els.bar) els.bar.style.width = p.percent + '%';
        setText(els.percent, p.percent + '%');
        setText(els.total, p.total);
        setText(els.imported, p.imported);
        setText(els.failed, p.failed);
        setText(els.skipped, p.skipped);
        setText(els.pending, p.pending);
        setText(els.processed, p.processed);
        setText(els.total2, p.total);
        setText(els.metaCurrentUrl, p.current_url || p.next_pending_url || '—');

        state.completed = !!p.completed;
        state.paused = !!p.paused;

        if (p.completed) {
            setText(els.label, 'Complete');
            stopLoop();
            if (els.done) els.done.classList.remove('hidden');
        } else if (p.paused) {
            setText(els.label, 'Paused');
            stopLoop();
        } else if (running) {
            setText(els.label, 'Importing…');
        } else {
            setText(els.label, 'Ready');
        }

        setText(els.detail, 'Processed: ' + p.processed + ' / ' + p.total + ' — Oldest first');
        updateButtons(p);
    }

    async function fetchJson(url, method) {
        const res = await fetch(url, {
            method: method,
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
            throw new Error('Invalid JSON (HTTP ' + res.status + '): ' + text.substring(0, 200));
        }

        if (!res.ok) {
            throw new Error(data?.error || data?.message || ('HTTP ' + res.status));
        }

        return data;
    }

    function scheduleNext(delayMs) {
        if (loopTimer) clearTimeout(loopTimer);
        if (!running || state.completed || state.paused) return;
        loopTimer = setTimeout(runOnce, delayMs);
    }

    function stopLoop() {
        running = false;
        if (loopTimer) {
            clearTimeout(loopTimer);
            loopTimer = null;
        }
    }

    async function runOnce() {
        if (!running || state.completed || state.paused) return;

        setText(els.label, 'Importing…');

        try {
            const data = await fetchJson(urls.process, 'POST');
            consecutiveFailures = 0;
            clearError();

            if (data?.progress) {
                updateProgress(data.progress);
            }

            (data?.results || []).forEach(function (r) {
                if (!r?.item?.url) return;
                const url = r.item.url;
                if (r.status === 'done') log('✓ ' + url);
                else if (r.status === 'skipped') log('↷ ' + url);
                else if (r.status === 'failed') log('✗ ' + url + (r.message ? ' — ' + r.message : ''));
            });

            if (data?.paused || data?.progress?.paused) {
                state.paused = true;
                stopLoop();
                log('Import paused.');
                return;
            }

            if (data?.progress?.completed) {
                log('Import complete.');
                return;
            }

            scheduleNext(randomDelay());
        } catch (e) {
            consecutiveFailures++;
            showError(e.message || String(e));

            if (consecutiveFailures >= MAX_RETRIES) {
                log('Too many errors — pausing batch.');
                try {
                    await fetchJson(urls.pause, 'POST');
                    const status = await fetchJson(urls.status, 'GET');
                    if (status) updateProgress(status);
                } catch (pauseErr) {
                    showError('Could not pause: ' + (pauseErr.message || String(pauseErr)));
                }
                state.paused = true;
                stopLoop();
                return;
            }

            log('Retry ' + consecutiveFailures + '/' + MAX_RETRIES + ' in 10s…');
            scheduleNext(RETRY_WAIT_MS);
        }
    }

    function startLoop() {
        if (running || state.completed || state.paused) return;
        running = true;
        clearError();
        log('Import started (one campaign per request).');
        runOnce();
    }

    if (els.btnPause) {
        els.btnPause.addEventListener('click', function () {
            stopLoop();
            fetchJson(urls.pause, 'POST')
                .then(function (data) {
                    state.paused = true;
                    if (data?.progress) updateProgress(data.progress);
                    log('Paused by user.');
                })
                .catch(function (e) {
                    showError(e.message || String(e));
                });
        });
    }

    if (els.btnResume) {
        els.btnResume.addEventListener('click', function () {
            fetchJson(urls.resume, 'POST')
                .then(function (data) {
                    state.paused = false;
                    if (data?.progress) updateProgress(data.progress);
                    log('Resumed.');
                    startLoop();
                })
                .catch(function (e) {
                    showError(e.message || String(e));
                });
        });
    }

    if (els.btnRetry) {
        els.btnRetry.addEventListener('click', function () {
            fetchJson(urls.retry, 'POST')
                .then(function (data) {
                    state.paused = false;
                    if (data?.progress) updateProgress(data.progress);
                    log('Re-queued ' + (data.retried || 0) + ' failed item(s).');
                    startLoop();
                })
                .catch(function (e) {
                    showError(e.message || String(e));
                });
        });
    }

    if (root.dataset.autoStart === '1' && !state.completed && !state.paused) {
        startLoop();
    } else if (state.paused) {
        log('Import is paused. Click Resume to continue.');
    } else if (state.completed) {
        log('Batch already completed.');
    }
});
</script>
@endpush
