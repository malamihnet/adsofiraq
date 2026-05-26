@extends('layouts.app')

@section('title', 'About Ads of Iraq')
@section('meta_description', 'Ads of Iraq is an independent advertising archive and creative community documenting campaigns, films, and creative work from Iraq and the region.')

@section('content')
<x-page-shell
    label="About"
    title="About Ads of Iraq"
    intro="Ads of Iraq is an independent advertising archive and creative community dedicated to documenting, showcasing, and organizing advertising work from Iraq and the region."
>
    <x-page-section heading="What we archive">
        <p>The platform focuses on campaigns, films, design, production, brands, agencies, and the people behind the work. We organize submissions so creative output can be discovered, referenced, and appreciated over time.</p>
    </x-page-section>

    <x-page-section heading="Who it is for">
        <p>Ads of Iraq serves agencies, brands, freelancers, production teams, students, and anyone interested in Iraqi and regional advertising culture. Members can browse publicly, contribute submissions, and follow creators they admire.</p>
    </x-page-section>

    <x-page-section heading="Our approach">
        <p>We believe advertising is cultural record as much as commercial communication. The archive is built with editorial care—submissions are reviewed, metadata is organized, and featured placements reflect work worth highlighting.</p>
    </x-page-section>

    <x-page-section heading="Independent platform">
        <p>Ads of Iraq is operated as an independent project focused on preservation and community. We are not affiliated with any single agency or brand, and we aim to represent a broad range of creative voices.</p>
    </x-page-section>
</x-page-shell>
@endsection
