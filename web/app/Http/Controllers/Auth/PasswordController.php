<?php

namespace App\Http\Controllers\Auth;

use App\Constants\LokerLensCopy;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            // Pesan lain (minimal 8 karakter, konfirmasi tidak cocok) dari lang/id/validation.php
            'current_password.required'         => LokerLensCopy::PROFIL_SANDI_LAMA_WAJIB,
            'current_password.current_password' => LokerLensCopy::PROFIL_SANDI_LAMA_SALAH,
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
