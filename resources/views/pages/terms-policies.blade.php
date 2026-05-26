@extends('layouts.app')

@section('title', 'Terms & Policies — Ads of Iraq')
@section('meta_description', 'Terms and policies for using Ads of Iraq, including account responsibility, submissions, content standards, and moderation.')

@section('content')
<x-page-shell
    label="Legal"
    title="Terms & Policies"
    intro="By using Ads of Iraq, you agree to the following terms. These policies keep the archive safe, respectful, and useful for everyone."
>
    <x-page-section heading="Account responsibility">
        <p>You are responsible for activity on your account and for keeping your login credentials secure. Provide accurate registration information and notify us if you suspect unauthorized access.</p>
    </x-page-section>

    <x-page-section heading="Submissions & ownership">
        <p>You are responsible for the content you submit. By uploading campaigns, you confirm that you have the right to share the materials and that your submission does not infringe third-party rights.</p>
        <p>Submitting work does not transfer ownership to Ads of Iraq. You retain your rights subject to the license implied by making content available on the platform.</p>
    </x-page-section>

    <x-page-section heading="Prohibited content">
        <p>Do not upload illegal, harmful, deceptive, or abusive material. Content that promotes violence, hatred, or unlawful activity is not permitted.</p>
        <p>Only upload media you have permission to share. Do not submit confidential, stolen, or misrepresented work.</p>
    </x-page-section>

    <x-page-section heading="Moderation & removal">
        <p>Ads of Iraq may approve, reject, edit metadata, feature, or remove submissions at its discretion. We may suspend or terminate accounts that violate these policies or abuse the platform.</p>
    </x-page-section>

    <x-page-section heading="Changes">
        <p>We may update these terms from time to time. Continued use of the platform after changes take effect constitutes acceptance of the updated policies.</p>
    </x-page-section>
</x-page-shell>
@endsection
