@extends('admin.auth.layout')

@section('title', 'Sign in')

@section('content')
    <h1 class="text-2xl font-semibold">Sign in</h1>
    <p class="mt-1 text-sm a-muted">Use your DXCOVR admin account.</p>

    @if (session('status'))
        <div class="a-alert a-alert-success mt-6" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <x-admin.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <div>
            <x-admin.input name="password" type="password" label="Password" required autocomplete="current-password" />
            <a href="{{ route('password.request') }}" class="mt-2 inline-block text-xs a-muted hover:text-text">Forgot your password?</a>
        </div>
        <label class="flex items-center gap-2.5 text-sm">
            <input type="checkbox" name="remember" value="1" class="a-checkbox !mt-0">
            Keep me signed in on this device
        </label>
        <button type="submit" class="a-btn a-btn-primary w-full">Sign in</button>
    </form>
@endsection
