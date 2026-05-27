@php
    $submitUrl = auth()->check()
        ? (auth()->user()->hasVerifiedEmail() ? route('campaigns.create') : route('verification.notice'))
        : route('login');

    $bookmarksUrl = auth()->check() && auth()->user()->hasVerifiedEmail()
        ? route('bookmarks.index')
        : (auth()->check() ? route('verification.notice') : route('login'));

    $followingUrl = auth()->check() && auth()->user()->hasVerifiedEmail()
        ? route('following.index')
        : (auth()->check() ? route('verification.notice') : route('login'));

    $navLink = fn (string $routePattern, string $href, string $label) => request()->routeIs($routePattern)
        ? 'text-white'
        : 'text-white/70 hover:text-white';

    $accountLink = 'text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 transition-colors hover:text-white';

    $leftLinks = [
        ['route' => 'campaigns.*', 'href' => route('campaigns.index'), 'label' => 'Campaigns'],
        ['route' => 'agencies.*', 'href' => route('agencies.index'), 'label' => 'Agencies'],
        ['route' => 'brands.*', 'href' => route('brands.index'), 'label' => 'Brands'],
        ['route' => 'people.*', 'href' => route('people.index'), 'label' => 'People'],
        ['route' => 'campaigns.create', 'href' => $submitUrl, 'label' => 'Submit'],
    ];

    $logoUrl = url('/images/Logo-main.svg');

    $authUser = auth()->user();
    $displayName = $authUser?->name ?: $authUser?->username;
@endphp

<header class="site-header border-b border-white/10 bg-black text-white" x-data="{ open: false }">
    <div class="site-header__bar mx-auto grid min-h-14 max-w-7xl grid-cols-[1fr_auto_1fr] items-center px-4 md:min-h-[72px] md:px-8">
        {{-- Left: platform navigation --}}
        <div class="flex items-center justify-start">
            <nav aria-label="Primary" class="hidden items-center gap-5 md:flex">
                @foreach($leftLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @class([
                            'text-[11px] font-medium uppercase tracking-[0.14em] transition-colors duration-150',
                            $navLink($link['route'], $link['href'], $link['label']),
                        ])
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Center: brand logo --}}
        <a
            href="{{ route('home') }}"
            class="inline-flex shrink-0 items-center justify-self-center brightness-0 invert transition-opacity hover:opacity-80"
            aria-label="Ads of Iraq"
        >
            <img
                src="{{ $logoUrl }}"
                alt="Ads of Iraq"
                class="block h-[40px] w-auto max-w-[180px] md:h-[52px] md:max-w-[220px]"
                decoding="async"
                fetchpriority="high"
            >
        </a>

        {{-- Right: account actions + mobile menu --}}
        <div class="site-header__actions flex flex-wrap items-center justify-end gap-3 md:gap-4">
            <div class="hidden items-center gap-4 md:flex">
            @guest
                <a href="{{ route('login') }}" class="{{ $accountLink }}">Login</a>
                <a href="{{ route('register') }}" class="{{ $accountLink }}">Register</a>
            @else
                <a
                    href="{{ route('profile.show.redirect') }}"
                    class="inline-flex max-w-[160px] items-center gap-2 transition-opacity hover:opacity-80"
                >
                    <x-user-avatar :user="$authUser" size="md" />
                    <span class="truncate text-[11px] font-medium tracking-wide text-white">{{ $displayName }}</span>
                </a>

                <a href="{{ route('profile.campaigns') }}" class="{{ $accountLink }} {{ request()->routeIs('profile.campaigns') ? '!text-white' : '' }}">My Campaigns</a>

                @if($authUser->hasVerifiedEmail())
                    <a href="{{ $bookmarksUrl }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-[0.14em] {{ request()->routeIs('bookmarks.*') ? 'text-white' : 'text-white/70 hover:text-white' }}" aria-label="Bookmarks">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.18V21L12 17.25 4.5 21V5.502c0-1.103.806-2.052 1.907-2.18a48.567 48.567 0 0112.186 0z"/>
                        </svg>
                        <span>Bookmarks</span>
                    </a>
                    <a href="{{ $followingUrl }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-[0.14em] {{ request()->routeIs('following.*') ? 'text-white' : 'text-white/70 hover:text-white' }}" aria-label="Watching">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Watching</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 transition-colors hover:text-white" aria-label="Logout">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('verification.notice') }}" class="{{ $accountLink }}">Verify Email</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 transition-colors hover:text-white" aria-label="Logout">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                @endif
            @endguest
            </div>

            {{-- Mobile menu toggle --}}
            <button
                type="button"
                class="inline-flex items-center justify-center text-white/80 transition-colors hover:text-white md:hidden"
                @click="open = !open"
                :aria-expanded="open"
                aria-controls="site-mobile-nav"
                aria-label="Menu"
            >
                <svg x-show="!open" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="open" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile navigation --}}
    <div
        id="site-mobile-nav"
        x-show="open"
        x-cloak
        @click.away="open = false"
        class="border-t border-white/10 bg-black md:hidden"
    >
        <nav aria-label="Mobile" class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3">
            @foreach($leftLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    @click="open = false"
                    @class([
                        'px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] transition-colors',
                        request()->routeIs($link['route']) ? 'text-white' : 'text-white/70 hover:text-white',
                    ])
                >
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="my-2 border-t border-white/10"></div>

            @guest
                <a href="{{ route('login') }}" @click="open = false" class="px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">Login</a>
                <a href="{{ route('register') }}" @click="open = false" class="px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">Register</a>
            @else
                <div class="flex items-center gap-3 px-2 py-3">
                    <a href="{{ route('profile.show.redirect') }}" @click="open = false" class="flex min-w-0 items-center gap-3">
                        <x-user-avatar :user="$authUser" size="lg" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-white">{{ $displayName }}</p>
                            <p class="truncate text-xs text-white/60">{{ '@'.$authUser->username }}</p>
                        </div>
                    </a>
                </div>

                <div class="my-2 border-t border-white/10"></div>

                <a href="{{ route('profile.campaigns') }}" @click="open = false" class="px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">My Campaigns</a>

                @if($authUser->hasVerifiedEmail())
                    <a href="{{ $bookmarksUrl }}" @click="open = false" class="inline-flex items-center gap-2 px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.18V21L12 17.25 4.5 21V5.502c0-1.103.806-2.052 1.907-2.18a48.567 48.567 0 0112.186 0z"/>
                        </svg>
                        Bookmarks
                    </a>
                    <a href="{{ $followingUrl }}" @click="open = false" class="inline-flex items-center gap-2 px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Watching
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center gap-2 px-2 py-2 text-left text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('verification.notice') }}" @click="open = false" class="px-2 py-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">Verify Email</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center gap-2 px-2 py-2 text-left text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 hover:text-white">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                @endif
            @endguest
        </nav>
    </div>
</header>

<style>[x-cloak] { display: none !important; }</style>
