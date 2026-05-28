@extends('layouts.app')

@section('title', $agency->seo_title)
@section('meta_description', $agency->seo_description)
@if($agency->logo_url)
    @section('og_image', $agency->logo_url)
@endif

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
  <x-authority-profile-hero
      :name="$agency->name"
      :verified="$agency->is_verified"
      :subtitle="$agency->is_production_house ? 'Production House' : 'Agency'"
      :bio="$agency->bio"
      :logo-url="$agency->logo_url"
      :cover-url="$agency->cover_url"
      :website-url="$agency->website_url"
      :socials="[
          'Instagram' => $agency->instagram_url,
          'LinkedIn' => $agency->linkedin_url,
          'X' => $agency->twitter_url,
      ]"
      :stats="[
          'Campaigns' => $stats['campaigns'],
          'Views' => $stats['views'],
          'Saves' => $stats['bookmarks'],
          'Years active' => $stats['years_active'] !== [] ? implode(', ', $stats['years_active']) : '—',
      ]"
  />

  @if($featuredCampaigns->isNotEmpty())
    <section class="mb-12">
      <h2 class="section-label mb-6">Featured work</h2>
      <x-campaign-grid :campaigns="$featuredCampaigns" />
    </section>
  @endif

  <section>
    <h2 class="section-label mb-6">All campaigns</h2>
    <x-campaign-grid :campaigns="$campaigns" />
    <div class="mt-8">{{ $campaigns->links() }}</div>
  </section>

  @if($collaboratingBrands->isNotEmpty())
    <section class="mt-12 border-t border-archive-border pt-10">
      <h2 class="section-label mb-4">Brand collaborations</h2>
      <div class="flex flex-wrap gap-3">
        @foreach($collaboratingBrands as $brand)
          <a href="{{ route('brand.show', $brand) }}" class="border border-archive-border px-3 py-1 text-sm hover:bg-archive-cream">{{ $brand->name }}</a>
        @endforeach
      </div>
    </section>
  @endif

  @if($relatedAgencies->isNotEmpty())
    <section class="mt-12 border-t border-archive-border pt-10">
      <h2 class="section-label mb-4">Related agencies</h2>
      <div class="flex flex-wrap gap-3">
        @foreach($relatedAgencies as $related)
          <a href="{{ route('agency.show', $related) }}" class="border border-archive-border px-3 py-1 text-sm hover:bg-archive-cream">{{ $related->name }}</a>
        @endforeach
      </div>
    </section>
  @endif
</div>
@endsection
