@extends('admin.auth.layout')

@section('title', 'Reset your password')

@section('content')
    <h1 class="text-2xl font-semibold">Reset your password</h1>
    <p class="mt-1 text-sm a-muted">Enter your account email. If it matches an account, we will send a reset link.</p>

    @if (session('status'))
        <div class="a-alert a-alert-success mt-6" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-admin.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <button type="submit" class="a-btn a-btn-primary w-full">Email reset link</button>
    </form>
    <a href="{{ route('login') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm a-muted hover:text-text"><x-icon name="arrow-left" :size="14" /> Back to sign in</a>
@endsection
