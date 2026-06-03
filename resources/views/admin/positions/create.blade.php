@extends('layouts.admin')

@section('title', 'Add Position — Admin')

@section('content')
<div class="mb-8"><a href="{{ route('admin.positions.index') }}" class="text-sm underline">&larr; All positions</a></div>
<h1 class="section-title mb-8">Add position</h1>

<form method="POST" action="{{ route('admin.positions.store') }}" class="max-w-3xl space-y-6">
    @csrf
    @include('admin.positions._form')
    <button type="submit" class="btn-primary text-xs">Create position</button>
</form>
@endsection
