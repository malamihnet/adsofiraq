@extends('layouts.app')

@section('title', 'Editorial Standards — Ads of Iraq')
@section('meta_description', 'Editorial standards for Ads of Iraq, including review, verification, featured placement, and correction requests.')

@section('content')
<x-page-shell
    label="Editorial"
    title="Editorial Standards"
    intro="Ads of Iraq aims to keep the archive accurate, organized, and representative of quality work from Iraq and the region."
>
    <x-page-section heading="Review before publication">
        <p>Campaigns are reviewed before they appear publicly. Our team checks submissions for completeness, clarity, and basic accuracy of metadata such as brand, agency, credits, and media.</p>
    </x-page-section>

    <x-page-section heading="Accuracy & organization">
        <p>We organize work using consistent taxonomy—brands, agencies, industries, mediums, and countries—so the archive remains searchable and useful. Submissions with missing or unclear information may be held until corrected.</p>
    </x-page-section>

    <x-page-section heading="Verification badge">
        <p>The “Verified by Ads of Iraq” badge indicates that a user, brand, agency, or campaign has been reviewed and confirmed by our team. Verification is granted at our discretion and may be removed if standards are not maintained.</p>
    </x-page-section>

    <x-page-section heading="Featured placement">
        <p>Homepage hero slider and featured campaign selections are editorial decisions. They highlight approved work that reflects the breadth and quality of the archive—not paid placement by default.</p>
    </x-page-section>

    <x-page-section heading="Corrections">
        <p>If you notice an error in a campaign listing, credit, or metadata, contact us at <a href="mailto:info@adsofiraq.com" class="underline hover:text-archive-gray">info@adsofiraq.com</a>. We welcome corrections from contributors and rights holders.</p>
    </x-page-section>
</x-page-shell>
@endsection
