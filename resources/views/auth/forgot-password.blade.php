@extends('layouts.app')

@section('title', 'Forgot Password — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-md px-4 py-16 md:px-8">
    <h1 class="section-title mb-8 text-center">Forgot Password</h1>
    @if(session('status'))
        <p class="mb-4 text-center text-sm text-green-700">{{ session('status') }}</p>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf
        <div>
            <label class="section-label mb-2 block">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="input-field">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary w-full">Send Reset Link</button>
    </form>
</div>
@endsection
