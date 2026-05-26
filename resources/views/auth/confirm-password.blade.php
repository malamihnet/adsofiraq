@extends('layouts.app')

@section('title', 'Confirm Password — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-md px-4 py-16">
    <h1 class="section-title mb-8 text-center">Confirm Password</h1>
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-6">
        @csrf
        <div>
            <label class="section-label mb-2 block">Password</label>
            <input type="password" name="password" required class="input-field">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary w-full">Confirm</button>
    </form>
</div>
@endsection
