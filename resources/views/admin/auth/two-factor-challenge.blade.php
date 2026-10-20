@extends('admin.auth.layout')

@section('title', 'Two-factor authentication')

@section('content')
    <div x-data="{ recovery: {{ $errors->has('recovery_code') ? 'true' : 'false' }} }">
        <h1 class="text-2xl font-semibold">Two-factor authentication</h1>
        <p class="mt-1 text-sm a-muted" x-show="! recovery">Enter the 6-digit code from your authenticator app.</p>
        <p class="mt-1 text-sm a-muted" x-show="recovery" x-cloak>Enter one of your emergency recovery codes.</p>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div x-show="! recovery">
                <x-admin.input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" autofocus maxlength="6" pattern="[0-9]*" ::disabled="recovery" />
            </div>
            <div x-show="recovery" x-cloak>
                <x-admin.input name="recovery_code" label="Recovery code" autocomplete="one-time-code" ::disabled="! recovery" />
            </div>
            <button type="submit" class="a-btn a-btn-primary w-full">Continue</button>
        </form>

        <button type="button" class="mt-5 text-sm a-muted underline hover:text-text" @click="recovery = ! recovery" x-text="recovery ? 'Use an authentication code instead' : 'Use a recovery code instead'"></button>
    </div>
@endsection
