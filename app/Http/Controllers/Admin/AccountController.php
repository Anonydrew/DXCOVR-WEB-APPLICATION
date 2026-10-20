<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Personal profile, password and two-factor authentication. Changes are
 * submitted to Laravel Fortify's endpoints, which apply their own validation
 * and password-confirmation requirements.
 */
class AccountController extends AdminController
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('admin.account', [
            'user' => $user,
            'pendingTwoFactor' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'recoveryCodes' => $user->hasTwoFactorEnabled() && in_array(session('status'), ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true)
                ? $user->recoveryCodes()
                : [],
        ]);
    }
}
