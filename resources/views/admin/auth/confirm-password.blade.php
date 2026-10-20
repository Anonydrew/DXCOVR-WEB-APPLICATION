@extends('admin.auth.layout')

@section('title', 'Confirm your password')

@section('content')
    <h1 class="text-2xl font-semibold">Confirm your password</h1>
    <p class="mt-1 text-sm a-muted">This is a secure area. Please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-4">
        @csrf
        <x-admin.input name="password" type="password" label="Password" required autofocus autocomplete="current-password" />
        <button type="submit" class="a-btn a-btn-primary w-full">Confirm</button>
    </form>
@endsection
