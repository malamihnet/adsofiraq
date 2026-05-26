@extends('layouts.app')

@section('title', 'Reset Password — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-md px-4 py-16 md:px-8">
    <h1 class="section-title mb-8 text-center">Reset Password</h1>
    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label class="section-label mb-2 block">Email</label>
            <input type="email" name="email" value="{{ old('email', $request->email) }}" required class="input-field">
        </div>
        <div>
            <label class="section-label mb-2 block">Password</label>
            <input type="password" name="password" required class="input-field">
        </div>
        <div>
            <label class="section-label mb-2 block">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="input-field">
        </div>
        <button type="submit" class="btn-primary w-full">Reset Password</button>
    </form>
</div>
@endsection
