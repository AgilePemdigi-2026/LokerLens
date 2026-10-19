<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Menangani pendaftaran akun baru.
 * Berdasarkan SRS 2.6: hanya Masyarakat yang dapat registrasi mandiri.
 * Analis & Kepala Bidang diasumsikan sudah punya akun (dari admin).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
            // Wajib dicentang sesuai mockup 6.1
            'persetujuan' => ['accepted'],
        ], [
            'name.required'       => 'Nama Lengkap wajib diisi.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah terdaftar.',
            'password.required'   => 'Kata Sandi wajib diisi.',
            'password.min'        => 'Kata Sandi minimal 8 karakter.',
            'password.confirmed'  => 'Konfirmasi Kata Sandi tidak cocok.',
            'persetujuan.accepted' => 'Anda harus menyetujui Kebijakan Privasi & Syarat Layanan.',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'Masyarakat', // Registrasi mandiri hanya untuk Masyarakat
        ]);

        event(new Registered($user));
        Auth::login($user);

        // Masyarakat → redirect ke Beranda setelah login
        return redirect()->route('beranda');
    }
}
