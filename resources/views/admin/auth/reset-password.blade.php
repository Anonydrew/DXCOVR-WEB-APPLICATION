@extends('admin.auth.layout')

@section('title', 'Choose a new password')

@section('content')
    <h1 class="text-2xl font-semibold">Choose a new password</h1>
    <p class="mt-1 text-sm a-muted">At least 12 characters, with upper- and lower-case letters and a number.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-admin.input name="email" type="email" label="Email address" :value="$request->email" required autocomplete="username" />
        <x-admin.input name="password" type="password" label="New password" required autocomplete="new-password" />
        <x-admin.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <button type="submit" class="a-btn a-btn-primary w-full">Save password</button>
    </form>
@endsection
