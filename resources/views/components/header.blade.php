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

    $navLink = function (string|array $routePattern, string $href, string $label): string {
        $patterns = is_array($routePattern) ? $routePattern : [$routePattern];
        $active = collect($patterns)->contains(fn (string $pattern) => request()->routeIs($pattern));

        return $active ? 'text-white' : 'text-white/70 hover:text-white';
    };

    $mobileNavLink = function (string|array $routePattern): string {
        $patterns = is_array($routePattern) ? $routePattern : [$routePattern];
        $active = collect($patterns)->contains(fn (string $pattern) => request()->routeIs($pattern));

        return $active ? 'text-archive-black' : 'text-archive-gray hover:text-archive-black';
    };

    $accountLink = 'text-[11px] font-medium uppercase tracking-[0.14em] text-white/70 transition-colors hover:text-white';
    $mobileAccountLink = 'text-[11px] font-medium uppercase tracking-[0.14em] text-archive-gray transition-colors hover:text-archive-black';

    $leftLinks = [
        ['route' => 'campaigns.*', 'href' => route('campaigns.index'), 'label' => 'Campaigns'],
        ['route' => ['agencies.*', 'agency.*'], 'href' => route('agencies.index'), 'label' => 'Agencies'],
        ['route' => ['brands.*', 'brand.*'], 'href' => route('brands.index'), 'label' => 'Brands'],
        ['route' => ['people.*', 'person.*'], 'href' => route('people.index'), 'label' => 'People'],
        ['route' => 'rankings.*', 'href' => route('rankings.index'), 'label' => 'Rankings'],
        ['route' => 'campaigns.create', 'href' => $submitUrl, 'label' => 'Submit', 'icon' => 'upload'],
    ];

    $uploadIcon = <<<'SVG'
<svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V3.75m0 0L8.25 7.5M12 3.75l3.75 3.75M4.5 13.5v4.875c0 .621.504 1.125 1.125 1.125h13.25c.621 0 1.125-.504 1.125-1.125V13.5"/>
</svg>
SVG;

    $logoUrl = url('/images/Logo-main.svg');

    $authUser = auth()->user();
    $displayName = $authUser?->name ?: $authUser?->username;

    $overHero = request()->routeIs('home');
@endphp

<header
    @class([
        'site-header max-md:pt-4 md:border-white/10 md:bg-black md:text-white md:backdrop-blur-none md:pt-0',
        $overHero
            ? 'site-header--over-hero max-md:absolute max-md:inset-x-0 max-md:top-0 max-md:z-[10000] max-md:border-b-0 max-md:bg-transparent max-md:text-white max-md:shadow-none max-md:backdrop-blur-none'
            : 'max-md:border-archive-border/50 max-md:bg-white/80 max-md:text-archive-black max-md:backdrop-blur-md',
    ])
    x-data="{ open: false }"
>
    <div class="site-header__bar mx-auto grid max-w-7xl grid-cols-[1fr_auto_1fr] items-center px-4 max-md:pb-2.5 md:min-h-[72px] md:px-8">
        {{-- Left: platform navigation --}}
        <div class="flex items-center justify-start">
            <nav aria-label="Primary" class="hidden items-center gap-5 md:flex">
                @foreach($leftLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        @class([
                            'text-[11px] font-medium uppercase tracking-[0.14em] transition-colors duration-150',
                            isset($link['icon']) ? 'inline-flex items-center gap-1.5' : '',
                            $navLink($link['route'], $link['href'], $link['label']),
                        ])
                        @if(($link['icon'] ?? null) === 'upload') aria-label="Submit campaign" @endif
                    >
                        @if(($link['icon'] ?? null) === 'upload')
                            {!! $uploadIcon !!}
                        @endif
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Center: brand logo --}}
        <a
            href="{{ route('home') }}"
            @class([
                'inline-flex shrink-0 items-center justify-self-center transition-opacity hover:opacity-80 md:brightness-0 md:invert',
                $overHero ? 'max-md:brightness-0 max-md:invert' : '',
            ])
            aria-label="Ads of Iraq"
        >
            <img
                src="{{ $logoUrl }}"
                alt="Ads of Iraq"
                class="block h-12 w-auto max-w-[200px] md:h-[52px] md:max-w-[220px]"
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
                @class([
                    'inline-flex h-10 w-10 items-center justify-center rounded-full border shadow-sm backdrop-blur transition-colors md:hidden',
                    $overHero
                        ? 'max-md:border-white/30 max-md:bg-black/35 max-md:text-white hover:max-md:border-white/50 hover:max-md:bg-black/50'
                        : 'border-neutral-200/70 bg-white/70 text-archive-black hover:border-neutral-300 hover:bg-white',
                ])
                @click="open = !open"
                :aria-expanded="open"
                aria-controls="site-mobile-nav"
                aria-label="Menu"
            >
                <svg x-show="!open" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                <svg x-show="open" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        @click.away="open = false"
        class="mx-3 mb-3 mt-1 overflow-hidden rounded-2xl border border-neutral-200/80 bg-white/95 shadow-[0_16px_48px_-20px_rgba(0,0,0,0.25)] backdrop-blur-md md:hidden"
    >
        <nav aria-label="Mobile" class="mx-auto flex max-w-7xl flex-col gap-0.5 px-3 py-3">
            @foreach($leftLinks as $link)
                <a
                    href="{{ $link['href'] }}"
                    @click="open = false"
                    @class([
                        'rounded-lg px-3 py-2.5 text-[11px] font-medium uppercase tracking-[0.12em] transition-colors',
                        isset($link['icon']) ? 'inline-flex items-center gap-2' : '',
                        $mobileNavLink($link['route']),
                    ])
                    @if(($link['icon'] ?? null) === 'upload') aria-label="Submit campaign" @endif
                >
                    @if(($link['icon'] ?? null) === 'upload')
                        {!! $uploadIcon !!}
                    @endif
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach

            <div class="my-2 border-t border-archive-border/60"></div>

            @guest
                <a href="{{ route('login') }}" @click="open = false" class="{{ $mobileAccountLink }} rounded-lg px-3 py-2.5">Login</a>
                <a href="{{ route('register') }}" @click="open = false" class="{{ $mobileAccountLink }} rounded-lg px-3 py-2.5">Register</a>
            @else
                <div class="flex items-center gap-3 rounded-lg px-3 py-3">
                    <a href="{{ route('profile.show.redirect') }}" @click="open = false" class="flex min-w-0 items-center gap-3">
                        <x-user-avatar :user="$authUser" size="lg" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-archive-black">{{ $displayName }}</p>
                            <p class="truncate text-xs text-archive-gray">{{ '@'.$authUser->username }}</p>
                        </div>
                    </a>
                </div>

                <div class="my-2 border-t border-archive-border/60"></div>

                @if($authUser->hasVerifiedEmail())
                    <a href="{{ $bookmarksUrl }}" @click="open = false" class="inline-flex items-center gap-2 rounded-lg px-3 py-2.5 text-[11px] font-medium uppercase tracking-[0.12em] {{ request()->routeIs('bookmarks.*') ? 'text-archive-black' : 'text-archive-gray hover:text-archive-black' }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.18V21L12 17.25 4.5 21V5.502c0-1.103.806-2.052 1.907-2.18a48.567 48.567 0 0112.186 0z"/>
                        </svg>
                        Bookmarks
                    </a>
                    <a href="{{ $followingUrl }}" @click="open = false" class="inline-flex items-center gap-2 rounded-lg px-3 py-2.5 text-[11px] font-medium uppercase tracking-[0.12em] {{ request()->routeIs('following.*') ? 'text-archive-black' : 'text-archive-gray hover:text-archive-black' }}">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Watching
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $mobileAccountLink }} inline-flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('verification.notice') }}" @click="open = false" class="{{ $mobileAccountLink }} rounded-lg px-3 py-2.5">Verify Email</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="{{ $mobileAccountLink }} inline-flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left">
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
