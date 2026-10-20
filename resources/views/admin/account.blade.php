@extends('layouts.admin')

@section('title', 'Account & security')

@php
    $statusMessages = [
        'profile-information-updated' => 'Your profile has been updated.',
        'password-updated' => 'Your password has been changed.',
        'two-factor-authentication-enabled' => 'Scan the QR code with your authenticator app, then enter a code to finish setting up two-factor authentication.',
        'two-factor-authentication-confirmed' => 'Two-factor authentication is on. Store your recovery codes somewhere safe.',
        'two-factor-authentication-disabled' => 'Two-factor authentication has been turned off.',
        'recovery-codes-generated' => 'New recovery codes generated. Your old codes no longer work.',
    ];
@endphp

@section('content')
    <x-admin.page-header title="Account & security" description="Manage your profile, password and sign-in protection." />

    @if (isset($statusMessages[session('status')]))
        <div class="a-alert a-alert-success mb-6" role="status">{{ $statusMessages[session('status')] }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="a-card" aria-labelledby="profile-heading">
            <div class="a-card-header"><h2 id="profile-heading" class="a-card-title">Profile</h2></div>
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="a-card-body space-y-4">
                @csrf
                @method('PUT')
                @php
                    $bag = $errors->getBag('updateProfileInformation');
                @endphp
                <div>
                    <label for="p-name" class="a-label">Name</label>
                    <input id="p-name" name="name" value="{{ old('name', $user->name) }}" class="a-input" required maxlength="255">
                    @if ($bag->has('name'))<p class="a-error">{{ $bag->first('name') }}</p>@endif
                </div>
                <div>
                    <label for="p-email" class="a-label">Email</label>
                    <input id="p-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="a-input" required maxlength="255" autocomplete="username">
                    @if ($bag->has('email'))<p class="a-error">{{ $bag->first('email') }}</p>@endif
                </div>
                <div>
                    <label for="p-title" class="a-label">Job title</label>
                    <input id="p-title" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="a-input" maxlength="120">
                    <p class="a-hint">Shown under your name on articles you write.</p>
                </div>
                <div>
                    <label for="p-bio" class="a-label">Short bio</label>
                    <textarea id="p-bio" name="bio" rows="3" class="a-input" maxlength="1000">{{ old('bio', $user->bio) }}</textarea>
                    <p class="a-hint">Shown at the end of your articles.</p>
                </div>
                <button type="submit" class="a-btn a-btn-primary">Save profile</button>
            </form>
        </section>

        <section class="a-card" aria-labelledby="password-heading">
            <div class="a-card-header"><h2 id="password-heading" class="a-card-title">Password</h2></div>
            <form method="POST" action="{{ route('user-password.update') }}" class="a-card-body space-y-4">
                @csrf
                @method('PUT')
                @php
                    $bag = $errors->getBag('updatePassword');
                @endphp
                <div>
                    <label for="pw-current" class="a-label">Current password</label>
                    <input id="pw-current" type="password" name="current_password" class="a-input" required autocomplete="current-password">
                    @if ($bag->has('current_password'))<p class="a-error">{{ $bag->first('current_password') }}</p>@endif
                </div>
                <div>
                    <label for="pw-new" class="a-label">New password</label>
                    <input id="pw-new" type="password" name="password" class="a-input" required autocomplete="new-password">
                    <p class="a-hint">At least 12 characters with upper- and lower-case letters and a number.</p>
                    @if ($bag->has('password'))<p class="a-error">{{ $bag->first('password') }}</p>@endif
                </div>
                <div>
                    <label for="pw-confirm" class="a-label">Confirm new password</label>
                    <input id="pw-confirm" type="password" name="password_confirmation" class="a-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="a-btn a-btn-primary">Change password</button>
            </form>
        </section>

        <section id="security" class="a-card xl:col-span-2" aria-labelledby="tfa-heading">
            <div class="a-card-header">
                <h2 id="tfa-heading" class="a-card-title">Two-factor authentication</h2>
                @if ($user->hasTwoFactorEnabled())
                    <span class="a-badge a-badge-success">On</span>
                @else
                    <span class="a-badge a-badge-warning">Off</span>
                @endif
            </div>
            <div class="a-card-body space-y-5 text-sm">
                <p class="a-muted max-w-2xl">Two-factor authentication adds a second step when you sign in: a code from an authenticator app such as Google Authenticator, Microsoft Authenticator or 1Password.</p>

                @if ($pendingTwoFactor)
                    <div class="grid gap-6 md:grid-cols-[auto_1fr]">
                        <div class="rounded-xl bg-white p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
                        <div class="space-y-4">
                            <p>Scan this QR code with your authenticator app, or enter this setup key:</p>
                            <p class="font-mono text-base tracking-wider">{{ decrypt($user->two_factor_secret) }}</p>
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex max-w-sm flex-wrap items-end gap-3">
                                @csrf
                                <div class="flex-1">
                                    <label for="tfa-code" class="a-label">Code from the app</label>
                                    <input id="tfa-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="a-input" required>
                                    @if ($errors->getBag('confirmTwoFactorAuthentication')->has('code'))
                                        <p class="a-error">{{ $errors->getBag('confirmTwoFactorAuthentication')->first('code') }}</p>
                                    @endif
                                </div>
                                <button type="submit" class="a-btn a-btn-primary">Confirm</button>
                            </form>
                        </div>
                    </div>
                @elseif ($user->hasTwoFactorEnabled())
                    @if ($recoveryCodes !== [])
                        <div class="a-alert a-alert-warning">
                            <div>
                                <p class="font-semibold">Recovery codes</p>
                                <p class="mt-1">Each code can be used once if you lose access to your authenticator app. Store them in a password manager.</p>
                                <ul class="mt-3 grid gap-1 font-mono sm:grid-cols-2">
                                    @foreach ($recoveryCodes as $code)
                                        <li>{{ $code }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                            @csrf
                            <button type="submit" class="a-btn">Generate new recovery codes</button>
                        </form>
                        <form method="POST" action="{{ route('two-factor.disable') }}" data-confirm="Turn off two-factor authentication? Your account will be protected by your password only.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="a-btn a-btn-danger">Turn off</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('two-factor.enable') }}">
                        @csrf
                        <button type="submit" class="a-btn a-btn-primary"><x-icon name="shield" :size="16" /> Turn on two-factor authentication</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="a-card xl:col-span-2 a-card-body text-sm" aria-label="Account details">
            <dl class="grid gap-4 sm:grid-cols-3">
                <div><dt class="a-mono a-subtle">Roles</dt><dd class="mt-1">{{ $user->roles->pluck('name')->implode(', ') ?: 'None' }}</dd></div>
                <div><dt class="a-mono a-subtle">Last sign-in</dt><dd class="mt-1">{{ $user->last_login_at?->format('j M Y, H:i') ?? 'Not set' }}</dd></div>
                <div><dt class="a-mono a-subtle">Account created</dt><dd class="mt-1">{{ $user->created_at->format('j M Y') }}</dd></div>
            </dl>
        </section>
    </div>
@endsection
