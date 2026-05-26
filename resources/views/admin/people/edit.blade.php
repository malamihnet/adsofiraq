@extends('layouts.admin')

@section('title', 'Edit ' . $person->name . ' — Admin')

@section('content')
<div class="mb-8"><a href="{{ route('admin.people.show', $person) }}" class="text-sm underline">&larr; Back to profile</a></div>
<h1 class="section-title mb-8">Edit person</h1>

<form method="POST" action="{{ route('admin.people.update', $person) }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    @csrf
    @method('PUT')
    <x-admin.person-form-fields :person="$person" />
    <button type="submit" class="btn-primary">Save changes</button>
</form>
@endsection
