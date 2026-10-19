<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menangani sesi login pengguna.
 * NFR-SEC-03: pesan gagal login identik untuk semua kasus (email/sandi salah).
 * Setelah sukses → arahkan sesuai peran.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Redirect berdasarkan peran
        return redirect()->intended($this->redirectByRole($request->user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Tentukan halaman tujuan setelah login berdasarkan peran.
     * Masyarakat → Beranda; Analis → Tinjau Lowongan; Kepala Bidang → Tindak Lanjut.
     */
    private function redirectByRole(string $role): string
    {
        return match ($role) {
            'Analis'          => route('tinjau-lowongan.index'),
            'Kepala Bidang'   => route('tindak-lanjut.index'),
            default           => route('beranda'), // Masyarakat
        };
    }
}
