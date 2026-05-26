@extends('layouts.app')

@section('title', 'Submit & Advertise — Ads of Iraq')
@section('meta_description', 'Learn how to submit campaigns to Ads of Iraq and inquire about partnerships, featured placement, and advertising opportunities.')

@section('content')
<x-page-shell
    label="Contribute"
    title="Submit & Advertise"
    intro="Share work with the archive or reach out about partnerships and promotional opportunities on Ads of Iraq."
>
    <x-page-section heading="Submit a campaign">
        <p>Verified members can submit campaigns for review. Approved submissions appear in the public archive with full campaign details, media, and credits.</p>
        <p>Submissions should accurately represent the work and include proper attribution. Incomplete or unclear entries may be returned for revision.</p>
    </x-page-section>

    <x-page-section heading="Publication & review">
        <p>Not every submission is published immediately. Our team reviews entries for quality, accuracy, and rights considerations before they go live.</p>
        <p>You will be notified when a campaign is approved or if changes are needed.</p>
    </x-page-section>

    <x-page-section heading="Featured & homepage placement">
        <p>Homepage hero slider and featured campaign placements are selected editorially by the Ads of Iraq team. Placement is not automatic and is reserved for approved work that represents the archive at its best.</p>
    </x-page-section>

    <x-page-section heading="Partnerships & advertising">
        <p>For sponsorships, partnerships, or advertising inquiries, contact us at <a href="mailto:info@adsofiraq.com" class="underline hover:text-archive-gray">info@adsofiraq.com</a>. We welcome conversations with agencies, brands, and cultural partners aligned with our mission.</p>
    </x-page-section>
</x-page-shell>
@endsection
