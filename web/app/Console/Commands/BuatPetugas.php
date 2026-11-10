<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat akun petugas (Analis atau Kepala Bidang). Halaman Daftar hanya membuat akun
 * Masyarakat, jadi akun petugas di server produksi dibuat lewat perintah ini:
 *
 *     php artisan lokerlens:buat-petugas
 *
 * Peran, nama, email, dan kata sandi diketik saat perintah dijalankan.
 * Kata sandi tidak tampil di layar dan tidak tersimpan di riwayat terminal.
 */
class BuatPetugas extends Command
{
    protected $signature = 'lokerlens:buat-petugas';

    protected $description = 'Buat akun petugas (Analis atau Kepala Bidang)';

    /** Peran yang boleh dibuat lewat perintah ini (Masyarakat mendaftar sendiri). */
    public const PERAN = ['Analis', 'Kepala Bidang'];

    public function handle(): int
    {
        $this->info('Buat akun petugas LokerLens');

        $data = [
            'role'                  => $this->choice('Peran', self::PERAN),
            'name'                  => trim((string) $this->ask('Nama lengkap')),
            'email'                 => strtolower(trim((string) $this->ask('Email'))),
            'password'              => (string) $this->secret('Kata sandi (minimal 8 karakter)'),
            'password_confirmation' => (string) $this->secret('Ulangi kata sandi'),
        ];

        $validator = Validator::make($data, [
            'role'     => ['required', 'in:' . implode(',', self::PERAN)],
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique'       => 'Email sudah terdaftar.',
            'password.confirmed' => 'Kedua kata sandi tidak sama.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }
            $this->line('Akun tidak dibuat. Jalankan lagi perintahnya.');

            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
        ]);

        $this->info("Akun {$user->role} untuk {$user->name} ({$user->email}) berhasil dibuat.");

        return self::SUCCESS;
    }
}
