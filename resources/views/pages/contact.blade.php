@extends('layouts.app')

@section('title', 'Contact — Ads of Iraq')
@section('meta_description', 'Contact Ads of Iraq for general inquiries, verification support, and submission questions.')

@section('content')
<x-page-shell
    label="Get in touch"
    title="Contact"
    intro="Ads of Iraq welcomes agencies, brands, creators, production houses, and collaborators who want to contribute to or support the archive."
>
    <x-page-section heading="General inquiries">
        <p>For general questions, partnerships, and platform feedback:</p>
        <p><a href="mailto:info@adsofiraq.com" class="underline hover:text-archive-gray">info@adsofiraq.com</a></p>
    </x-page-section>

    <x-page-section heading="Verification & support">
        <p>For verification requests and account support:</p>
        <p><a href="mailto:verify@adsofiraq.com" class="underline hover:text-archive-gray">verify@adsofiraq.com</a></p>
    </x-page-section>

    <x-page-section heading="Submission questions">
        <p>For help with campaign submissions, edits, or review status:</p>
        <p><a href="mailto:info@adsofiraq.com" class="underline hover:text-archive-gray">info@adsofiraq.com</a></p>
    </x-page-section>

    <x-page-section heading="Response times">
        <p>We read every message and aim to respond as quickly as possible. Complex verification or submission matters may take a little longer while we review details carefully.</p>
    </x-page-section>
</x-page-shell>
@endsection
