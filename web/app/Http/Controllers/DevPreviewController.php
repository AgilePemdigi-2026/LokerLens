<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DevPreview;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Halaman preview pengembangan (/dev). Route-nya hanya terdaftar jika
 * DevPreview::aktif() (lihat routes/web.php dan routes/dev.php).
 */
class DevPreviewController extends Controller
{
    public function index(): View
    {
        return view('dev.index', [
            'akunDemo' => LokerLensSeeder::AKUN_DEMO,
            'tautan'   => DevPreview::tautan(),
        ]);
    }

    /**
     * Masuk sebagai akun demo seeder untuk peran yang dipilih, lalu kembali ke /dev.
     */
    public function masuk(Request $request, string $peran): RedirectResponse
    {
        $email = LokerLensSeeder::AKUN_DEMO[$peran] ?? abort(404);

        $user = User::where('email', $email)->where('role', $peran)->first();
        if (! $user) {
            return redirect()->route('dev.index')
                ->with('error', "Akun demo {$peran} ({$email}) belum ada. Jalankan php artisan migrate:fresh --seed dari dalam web/.");
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dev.index')->with('success', "Masuk sebagai {$peran} ({$email}).");
    }
}
