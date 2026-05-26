@extends('layouts.app')

@section('title', 'Login — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-md px-4 py-16 md:px-8">
    <h1 class="section-title mb-8 text-center">Login</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf
        <div>
            <label class="section-label mb-2 block">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="input-field">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Password</label>
            <input type="password" name="password" required class="input-field">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded border-archive-border">
            Remember me
        </label>
        <button type="submit" class="btn-primary w-full">Login</button>
        <p class="text-center text-sm">
            <a href="{{ route('password.request') }}" class="underline">Forgot password?</a>
            &middot;
            <a href="{{ route('register') }}" class="underline">Register</a>
        </p>
    </form>
</div>
@endsection
