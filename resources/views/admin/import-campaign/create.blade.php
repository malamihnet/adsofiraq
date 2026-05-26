@extends('layouts.admin')

@section('title', 'Import Campaign — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm underline">&larr; All campaigns</a>
    <h1 class="section-title mt-4">Import Campaign</h1>
    <p class="mt-2 max-w-2xl text-sm text-archive-gray">
        Paste a campaign URL (for example, an Ads of the World page). Click Import to create a
        <strong>pending</strong> campaign immediately and open it for review. Nothing is published automatically.
    </p>
</div>

@if(session('error'))
    <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
        @if(session('duplicate_campaign_id'))
            @php $duplicate = \App\Models\Campaign::find(session('duplicate_campaign_id')); @endphp
            @if($duplicate)
                <p class="mt-2">
                    <a href="{{ route('admin.campaigns.edit', $duplicate) }}" class="underline">Open existing campaign</a>
                </p>
            @endif
        @endif
    </div>
@endif

<form method="POST" action="{{ route('admin.import-campaign.store') }}" class="max-w-2xl border border-archive-border bg-white p-6">
    @csrf

    <label for="url" class="section-label mb-2 block">Campaign URL</label>
    <input
        type="url"
        name="url"
        id="url"
        value="{{ old('url') }}"
        required
        placeholder="https://www.adsoftheworld.com/campaigns/..."
        class="input-field"
    >
    @error('url')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <p class="mt-3 text-xs text-archive-gray">
        Imported media is stored for admin review. Please verify usage rights before approving.
    </p>

    <button type="submit" class="btn-primary mt-6">Import Campaign</button>
</form>
@endsection
