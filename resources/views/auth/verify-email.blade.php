@extends('layouts.app')

@section('title', 'Verify Email — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-lg px-4 py-20 md:px-8">
    <p class="section-label mb-4 text-center">Account</p>
    <h1 class="section-title mb-6 text-center">Verify your email</h1>

    <div class="border border-archive-border p-8 text-center">
        <p class="text-lg leading-relaxed text-archive-black">
            Please verify your email address before continuing.
        </p>
        <p class="mt-4 text-sm leading-relaxed text-archive-gray">
            We sent a verification link to <strong class="text-archive-black">{{ auth()->user()->email }}</strong>.
            If you don’t see it, please check your spam or junk folder.
        </p>

        @if (session('success'))
            <p class="mt-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </p>
        @endif

        @if (session('error'))
            <p class="mt-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </p>
        @endif

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary w-full sm:w-auto">Resend verification email</button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-outline w-full sm:w-auto">Logout</button>
            </form>
        </div>
    </div>
</div>
@endsection
