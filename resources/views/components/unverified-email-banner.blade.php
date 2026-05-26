@if(auth()->check() && ! auth()->user()->hasVerifiedEmail() && ! request()->routeIs('verification.notice'))
    <div class="border-b border-archive-border bg-archive-light px-4 py-3">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 text-center sm:flex-row sm:text-left">
            <p class="text-sm text-archive-black">
                Your email address is not verified.
            </p>
            <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                @csrf
                <button type="submit" class="btn-outline text-xs">Resend</button>
            </form>
        </div>
    </div>
@endif
