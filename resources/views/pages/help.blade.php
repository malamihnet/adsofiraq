@extends('layouts.app')

@section('title', 'Help Center — Ads of Iraq')
@section('meta_description', 'Learn how to browse campaigns, create an account, submit work, and use bookmarks and following on Ads of Iraq.')

@section('content')
<x-page-shell
    label="Support"
    title="Help Center"
    intro="Everything you need to explore the archive, contribute campaigns, and stay connected with the Ads of Iraq community."
>
    <x-page-section heading="Browse campaigns">
        <p>Use the Campaigns archive to discover advertising work from Iraq and the region. Filter by brand, agency, industry, medium, country, or year, or search by title and keywords.</p>
        <p>Each campaign page includes videos, stills, credits, and related work to help you explore creative output in context.</p>
    </x-page-section>

    <x-page-section heading="Create an account">
        <p>Register for a free account to submit campaigns, save bookmarks, and follow contributors. Email verification is required before you can submit or use member features.</p>
        <p>If you already have an account, sign in from the header to access your profile, bookmarks, and following feed.</p>
    </x-page-section>

    <x-page-section heading="Submit a campaign">
        <p>Verified members can submit campaigns through the Submit Campaign form. Include title, description, credits, media, and taxonomy details such as brand, agency, industry, and medium.</p>
        <p>Submissions enter a review queue and are not published immediately. You can edit a pending or rejected submission from your campaign page.</p>
    </x-page-section>

    <x-page-section heading="Why submissions need approval">
        <p>Ads of Iraq reviews submissions to maintain quality, accuracy, and respect for rights holders. Approval helps keep the archive organized, trustworthy, and useful for agencies, brands, creators, and researchers.</p>
        <p>Rejected submissions may be revised and resubmitted. Our team may adjust metadata for clarity and consistency during review.</p>
    </x-page-section>

    <x-page-section heading="Bookmarks and watching">
        <p>Save campaigns to your bookmarks for quick access later. Watch campaigns to build a personal list you can revisit from your Watching page.</p>
        <p>You can also follow contributors from their profile pages. Bookmarks and watching are available to signed-in, verified members from campaign pages.</p>
    </x-page-section>
</x-page-shell>
@endsection
