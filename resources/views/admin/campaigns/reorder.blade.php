@extends('layouts.admin')

@section('title', 'Reorder Archive — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.campaigns.index') }}" class="text-sm underline">&larr; All campaigns</a>
        <h1 class="section-title mt-4">Archive Order</h1>
        <p class="mt-2 max-w-2xl text-sm text-archive-gray">
            Drag campaigns to set their order on the public archive (<strong>Latest</strong> sort).
            Check <strong>Pin manually</strong> for campaigns that should stay in a fixed position.
            Unpinned campaigns sort by approval date after all pinned items.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.campaigns.reorder.reset') }}" onsubmit="return confirm('Clear all manual archive positions?');">
            @csrf
            <button type="submit" class="btn-outline text-xs">Reset to automatic ordering</button>
        </form>
    </div>
</div>

<form
    id="campaign-reorder-form"
    method="POST"
    action="{{ route('admin.campaigns.reorder.update') }}"
    class="space-y-6"
>
    @csrf

    <div class="flex flex-wrap items-center justify-between gap-4 border border-archive-border bg-archive-light px-4 py-3">
        <p id="reorder-unsaved-hint" class="hidden text-sm text-amber-800">Unsaved changes — save when finished.</p>
        <p id="reorder-saved-hint" class="text-sm text-archive-gray">Drag rows to reorder. Toggle pins for fixed positions.</p>
        <button type="submit" class="btn-primary text-xs" id="reorder-save-btn">Save archive order</button>
    </div>

    <ul
        id="campaign-reorder-list"
        class="divide-y divide-archive-border border border-archive-border bg-white"
    >
        @foreach($campaigns as $campaign)
            <li
                class="campaign-reorder-item flex items-center gap-4 px-4 py-3 transition-colors"
                data-campaign-id="{{ $campaign->id }}"
            >
                <button
                    type="button"
                    class="drag-handle flex-shrink-0 cursor-grab touch-none p-2 text-archive-gray hover:text-archive-black active:cursor-grabbing"
                    aria-label="Drag to reorder"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
                        <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
                        <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
                    </svg>
                </button>

                <div class="h-14 w-20 flex-shrink-0 overflow-hidden border border-archive-border bg-archive-light">
                    @if($campaign->thumbnail_url)
                        <img src="{{ $campaign->thumbnail_url }}" alt="" class="h-full w-full object-cover">
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ $campaign->title }}</p>
                    <p class="mt-0.5 truncate text-xs text-archive-gray">
                        @if($campaign->brands->isNotEmpty())
                            {{ $campaign->brands->pluck('name')->join(', ') }}
                        @endif
                        @if($campaign->agencies->isNotEmpty())
                            @if($campaign->brands->isNotEmpty()) &middot; @endif
                            {{ $campaign->agencies->pluck('name')->join(', ') }}
                        @endif
                    </p>
                </div>

                <span class="hidden rounded border border-archive-border px-2 py-0.5 text-[10px] uppercase tracking-wider text-archive-gray sm:inline">
                    {{ $campaign->status }}
                </span>

                @if($campaign->manual_order)
                    <span class="rounded border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-amber-900">
                        Pinned manually
                    </span>
                @endif

                <label class="flex flex-shrink-0 items-center gap-2 text-xs">
                    <input
                        type="checkbox"
                        class="campaign-pin-checkbox rounded border-archive-border"
                        value="{{ $campaign->id }}"
                        @checked($campaign->manual_order !== null)
                    >
                    Pin manually
                </label>
            </li>
        @endforeach
    </ul>

    @if($campaigns->isEmpty())
        <p class="py-12 text-center text-archive-gray">No approved campaigns to reorder yet.</p>
    @endif
</form>
@endsection

@push('scripts')
    @vite('resources/js/admin-campaign-reorder.js')
@endpush
