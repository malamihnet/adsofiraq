@props(['action', 'model'])

<div class="border border-archive-border p-6">
    <p class="section-label mb-4">Platform verification</p>
    <form method="POST" action="{{ $action }}">
        @csrf
        @method('PUT')
        <label class="flex cursor-pointer items-center gap-3">
            <input
                type="checkbox"
                name="is_verified"
                value="1"
                class="rounded border-archive-border text-archive-black focus:ring-archive-black"
                @checked(old('is_verified', $model->is_verified))
            >
            <span class="text-sm">Verified by Ads of Iraq</span>
        </label>
        @if($model->is_verified && $model->verified_at)
            <p class="mt-3 text-xs text-archive-gray">
                Verified {{ $model->verified_at->format('M j, Y g:i A') }}
                @if($model->verifiedBy)
                    by {{ $model->verifiedBy->name }}
                @endif
            </p>
        @endif
        <button type="submit" class="btn-primary mt-4 text-xs">Save verification</button>
    </form>
</div>
