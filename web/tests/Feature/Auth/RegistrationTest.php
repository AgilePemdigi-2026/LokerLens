<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'persetujuan' => '1',
        ]);

        // Sejak PR #37: setelah daftar diarahkan ke login dengan pesan sukses, dan belum login
        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false))
            ->assertSessionHas('status', 'Akun berhasil dibuat. Silakan masuk.');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
