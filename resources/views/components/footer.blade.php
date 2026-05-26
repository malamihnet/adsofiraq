<footer class="mt-24 bg-black text-white">
    {{-- Top strip --}}
    <div class="bg-black">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-10 md:flex-row md:items-center md:justify-between md:gap-10 md:px-8 md:py-12">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:gap-6">
                <div class="shrink-0 brightness-0 invert">
                    <x-logo size="sm" />
                </div>
                {{-- Tagline options (swap active line as needed):
                    1. An independent archive documenting advertising, film, design, and creative culture from Iraq and the region.
                    2. Showcasing the campaigns, films, brands, agencies, and creative voices shaping Iraq's advertising industry.
                    3. A curated platform dedicated to preserving and showcasing advertising creativity from Iraq and beyond.
                    4. A living record of campaigns, motion, and design — preserving Iraq's place in global advertising culture.
                    5. Documenting the films, campaigns, and studios that define advertising across Iraq and the wider region.
                --}}
                <p class="max-w-md text-sm font-light leading-relaxed text-white/90">
                    An independent archive documenting advertising, film, design, and creative culture from Iraq and the region.
                </p>
            </div>

            <x-footer-social-links class="shrink-0 md:ml-auto" />
        </div>
    </div>

    {{-- Bottom strip --}}
    <div class="border-t border-white/10 bg-black text-white">
        <div class="mx-auto max-w-7xl px-4 py-6 md:px-8">
            <div class="flex flex-col items-center gap-4 text-center md:flex-row md:justify-between md:text-left">
                <p class="text-xs font-light tracking-wide text-white/70">
                    &copy; 2026 Ads of Iraq
                </p>

                <nav aria-label="Footer">
                    <ul class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs font-light tracking-wide text-white/70">
                        <li><a href="{{ route('pages.help') }}" class="transition-colors hover:text-white">Help Center</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="transition-colors hover:text-white">Contact</a></li>
                        <li><a href="{{ route('pages.about') }}" class="transition-colors hover:text-white">About Ads of Iraq</a></li>
                        <li><a href="{{ route('pages.submit-advertise') }}" class="transition-colors hover:text-white">Submit &amp; Advertise</a></li>
                        <li><a href="{{ route('pages.terms-policies') }}" class="transition-colors hover:text-white">Terms &amp; Policies</a></li>
                        <li><a href="{{ route('pages.editorial-standards') }}" class="transition-colors hover:text-white">Editorial Standards</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</footer>
