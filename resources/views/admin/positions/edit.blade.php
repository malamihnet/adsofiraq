@extends('layouts.admin')

@section('title', 'Edit Position — Admin')

@section('content')
<div class="mb-8"><a href="{{ route('admin.positions.index') }}" class="text-sm underline">&larr; All positions</a></div>
<h1 class="section-title mb-8">Edit position</h1>

<form method="POST" action="{{ route('admin.positions.update', $position) }}" class="max-w-3xl space-y-6">
    @csrf
    @method('PUT')
    @include('admin.positions._form', ['position' => $position])
    <button type="submit" class="btn-primary text-xs">Save changes</button>
</form>
@endsection
