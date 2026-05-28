@props(['graphs' => []])

@if(!empty($graphs))
    @php
        $service = app(\App\Services\StructuredDataService::class);
    @endphp
    {!! $service->toScriptTag($graphs) !!}
@endif
