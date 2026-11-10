<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    /**
     * Issue #25: fitur Hapus Akun dihapus untuk semua peran, karena menghapus akun
     * ikut menghapus riwayat kasus (cascadeOnDelete).
     */
    public function test_akun_tidak_bisa_dihapus_lewat_profil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405); // route DELETE /profile tidak ada lagi

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('profile.destroy'));
    }

    public function test_halaman_profil_tidak_menampilkan_hapus_akun_untuk_semua_peran(): void
    {
        foreach (['Masyarakat', 'Analis', 'Kepala Bidang'] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]))
                ->get('/profile')
                ->assertOk()
                ->assertSee(\App\Constants\LokerLensCopy::PROFIL_JUDUL)
                ->assertSee(\App\Constants\LokerLensCopy::PROFIL_SANDI_JUDUL)
                ->assertDontSee('Hapus Akun')
                ->assertDontSee('Delete Account')
                ->assertDontSee('Profile Information');
        }
    }
}
