@extends('layouts.app')

@section('title', $brand->seo_title)
@section('meta_description', $brand->seo_description)
@if($brand->logo_url)
    @section('og_image', $brand->logo_url)
@endif

@push('meta')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <x-structured-data :graphs="$schema" />
@endpush

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
  <x-authority-profile-hero
      :name="$brand->name"
      :verified="$brand->is_verified"
      subtitle="Brand"
      :bio="$brand->bio"
      :logo-url="$brand->logo_url"
      :cover-url="$brand->cover_url"
      :website-url="$brand->website_url"
      :socials="[
          'Instagram' => $brand->instagram_url,
          'LinkedIn' => $brand->linkedin_url,
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
      <h2 class="section-label mb-6">Featured campaigns</h2>
      <x-campaign-grid :campaigns="$featuredCampaigns" />
    </section>
  @endif

  <section>
    <h2 class="section-label mb-6">Campaign archive</h2>
    <x-campaign-grid :campaigns="$campaigns" />
    <div class="mt-8">{{ $campaigns->links() }}</div>
  </section>

  @if($collaboratingAgencies->isNotEmpty())
    <section class="mt-12 border-t border-archive-border pt-10">
      <h2 class="section-label mb-4">Agency collaborations</h2>
      <div class="flex flex-wrap gap-3">
        @foreach($collaboratingAgencies as $agency)
          <a href="{{ route('agency.show', $agency) }}" class="border border-archive-border px-3 py-1 text-sm hover:bg-archive-cream">{{ $agency->name }}</a>
        @endforeach
      </div>
    </section>
  @endif
</div>
@endsection
