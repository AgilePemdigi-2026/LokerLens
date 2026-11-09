<?php

namespace App\Http\Controllers\Auth;

use App\Constants\LokerLensCopy;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Layar 1: form masukkan email.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim tautan reset ke email.
     *
     * Respons SELALU sama, apa pun hasilnya (email terdaftar, tidak terdaftar,
     * atau sedang dibatasi throttle). Dengan begitu orang luar tidak bisa
     * menebak email siapa saja yang terdaftar di LokerLens.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            [
                'email.required' => LokerLensCopy::LUPA_EMAIL_WAJIB,
                'email.email'    => LokerLensCopy::LUPA_EMAIL_TIDAK_VALID,
            ]
        );

        // Hasilnya sengaja tidak dipakai (lihat penjelasan di atas).
        Password::sendResetLink($request->only('email'));

        return redirect()
            ->route('password.sent')
            ->with('email_reset', $request->input('email'));
    }

    /**
     * Layar 2: konfirmasi "tautan reset telah dikirim" (netral).
     * Hanya bisa dibuka setelah form dikirim; kalau dibuka langsung, kembali ke form.
     */
    public function terkirim(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('email_reset')) {
            return redirect()->route('password.request');
        }

        return view('auth.link-terkirim', [
            'email' => $request->session()->get('email_reset'),
        ]);
    }
}