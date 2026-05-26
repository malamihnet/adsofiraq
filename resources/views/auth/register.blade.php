@extends('layouts.app')

@section('title', 'Register — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-md px-4 py-16 md:px-8">
    <h1 class="section-title mb-8 text-center">Create Account</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf
        <div>
            <label class="section-label mb-2 block">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="input-field">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required class="input-field">
            @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="input-field">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Password</label>
            <input type="password" name="password" required class="input-field">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="input-field">
        </div>
        <button type="submit" class="btn-primary w-full">Register</button>
        <p class="text-center text-sm"><a href="{{ route('login') }}" class="underline">Already have an account?</a></p>
    </form>
</div>
@endsection
