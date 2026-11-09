<?php

namespace App\Http\Controllers\Auth;

use App\Constants\LokerLensCopy;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Layar 3: form atur kata sandi baru (dibuka dari tautan di email).
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Simpan kata sandi baru.
     */
    public function store(Request $request): RedirectResponse
    {
        // Aturan sandi sama dengan halaman Register (minimal 8 karakter).
        $request->validate(
            [
                'token'    => ['required'],
                'email'    => ['required', 'email'],
                'password' => ['required', 'confirmed', Rules\Password::min(8)],
            ],
            [
                'email.required'     => LokerLensCopy::LUPA_EMAIL_WAJIB,
                'email.email'        => LokerLensCopy::LUPA_EMAIL_TIDAK_VALID,
                'password.required'  => LokerLensCopy::SANDI_BARU_WAJIB,
                'password.min'       => LokerLensCopy::SANDI_BARU_MINIMAL,
                'password.confirmed' => LokerLensCopy::SANDI_BARU_TIDAK_COCOK,
            ]
        );

        // Password broker memeriksa token (cocok, belum kedaluwarsa, belum dipakai),
        // lalu menjalankan fungsi di bawah hanya jika semuanya valid.
        // Setelah berhasil, token otomatis dihapus, jadi tautan hanya sekali pakai.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password'       => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('password.success')
                ->with('sandi_diubah', true);
        }

        // Token salah, kedaluwarsa, sudah dipakai, atau email tidak cocok:
        // satu pesan yang sama, lalu arahkan untuk meminta tautan baru.
        return redirect()
            ->route('password.request')
            ->withErrors(['email' => LokerLensCopy::TAUTAN_RESET_TIDAK_VALID]);
    }

    /**
     * Layar 4: kata sandi berhasil diubah.
     * Hanya bisa dibuka tepat setelah reset berhasil.
     */
    public function berhasil(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('sandi_diubah')) {
            return redirect()->route('login');
        }

        return view('auth.reset-berhasil');
    }
}