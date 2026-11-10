<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Minta tautan reset untuk user, lalu kembalikan token aslinya
     * (token ditangkap dari notifikasi palsu, bukan dari email sungguhan).
     */
    private function mintaToken(User $user): string
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notifikasi) use (&$token) {
            $token = $notifikasi->token;

            return true;
        });

        return $token;
    }

    // ─── Layar 1: form email ────────────────────────────────────────────

    public function test_layar_form_lupa_password_tampil(): void
    {
        $this->get('/forgot-password')->assertOk();
    }

    public function test_email_kosong_atau_tidak_valid_ditolak(): void
    {
        $this->post('/forgot-password', ['email' => ''])->assertSessionHasErrors('email');
        $this->post('/forgot-password', ['email' => 'bukan-email'])->assertSessionHasErrors('email');
    }

    // ─── Layar 2: konfirmasi netral ─────────────────────────────────────

    public function test_email_terdaftar_dikirimi_tautan_dan_diarahkan_ke_layar_konfirmasi(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'Masyarakat']);

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.sent'))
            ->assertSessionHas('email_reset', $user->email);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_tidak_terdaftar_mendapat_respons_yang_sama_tanpa_email_terkirim(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'tidak.ada@contoh.com'])
            ->assertRedirect(route('password.sent'))
            ->assertSessionHas('email_reset', 'tidak.ada@contoh.com');

        Notification::assertNothingSent();
    }

    public function test_layar_konfirmasi_tampil_setelah_form_dikirim(): void
    {
        $this->withSession(['email_reset' => 'adel@contoh.com'])
            ->get(route('password.sent'))
            ->assertOk()
            ->assertSee('adel@contoh.com');
    }

    public function test_layar_konfirmasi_dibuka_langsung_kembali_ke_form(): void
    {
        $this->get(route('password.sent'))->assertRedirect(route('password.request'));
    }

    // ─── Layar 3: form sandi baru ───────────────────────────────────────

    public function test_layar_form_sandi_baru_tampil_dengan_token(): void
    {
        $user = User::factory()->create(['role' => 'Masyarakat']);
        $token = $this->mintaToken($user);

        $this->get('/reset-password/' . $token)->assertOk();
    }

    public function test_sandi_terlalu_pendek_atau_tidak_cocok_ditolak(): void
    {
        $user = User::factory()->create(['role' => 'Masyarakat']);
        $token = $this->mintaToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'Abcdefg1!',
            'password_confirmation' => 'BedaSandi1!',
        ])->assertSessionHasErrors('password');
    }

    // ─── Layar 4: berhasil ──────────────────────────────────────────────

    public function test_sandi_berhasil_diubah_dengan_token_valid(): void
    {
        $user = User::factory()->create(['role' => 'Masyarakat']);
        $token = $this->mintaToken($user);

        $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'Abcdefg1!',
            'password_confirmation' => 'Abcdefg1!',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.success'))
            ->assertSessionHas('sandi_diubah', true);

        $this->assertTrue(Hash::check('Abcdefg1!', $user->fresh()->password));
    }

    public function test_layar_berhasil_hanya_bisa_dibuka_tepat_setelah_reset(): void
    {
        $this->get(route('password.success'))->assertRedirect(route('login'));

        $this->withSession(['sandi_diubah' => true])
            ->get(route('password.success'))
            ->assertOk();
    }

    // ─── Keamanan token ─────────────────────────────────────────────────

    public function test_tautan_reset_tidak_bisa_dipakai_dua_kali(): void
    {
        $user = User::factory()->create(['role' => 'Masyarakat']);
        $token = $this->mintaToken($user);

        $data = [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'Abcdefg1!',
            'password_confirmation' => 'Abcdefg1!',
        ];

        $this->post('/reset-password', $data)->assertRedirect(route('password.success'));

        // Pemakaian kedua dengan token yang sama harus ditolak.
        $this->post('/reset-password', array_merge($data, [
            'password'              => 'SandiLain99!',
            'password_confirmation' => 'SandiLain99!',
        ]))
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        // Sandi tetap yang pertama.
        $this->assertTrue(Hash::check('Abcdefg1!', $user->fresh()->password));
    }

    public function test_token_palsu_ditolak(): void
    {
        $user = User::factory()->create(['role' => 'Masyarakat']);

        $this->post('/reset-password', [
            'token'                 => 'token-palsu',
            'email'                 => $user->email,
            'password'              => 'Abcdefg1!',
            'password_confirmation' => 'Abcdefg1!',
        ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('Abcdefg1!', $user->fresh()->password));
    }
}