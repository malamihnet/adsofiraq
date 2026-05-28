@props(['groups' => []])

@if(!empty($groups))
    <section class="mt-16 space-y-12 border-t border-archive-border pt-12">
        @foreach($groups as $heading => $campaigns)
            <div>
                <h2 class="section-label mb-6">{{ $heading }}</h2>
                <x-campaign-grid :campaigns="$campaigns" />
            </div>
        @endforeach
    </section>
@endif
