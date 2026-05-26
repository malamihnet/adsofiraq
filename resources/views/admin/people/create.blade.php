@extends('layouts.admin')

@section('title', 'Add Person — Admin')

@section('content')
<div class="mb-8"><a href="{{ route('admin.people.index') }}" class="text-sm underline">&larr; All people</a></div>
<h1 class="section-title mb-8">Add person</h1>

<form method="POST" action="{{ route('admin.people.store') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    @csrf
    <x-admin.person-form-fields :require-photo="true" />
    <button type="submit" class="btn-primary">Create person</button>
</form>
@endsection
