<?php

namespace Tests\Feature;

use App\Constants\LokerLensCopy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Issue #25: navbar (menu HP, tanpa lonceng, logo ke halaman awal), footer Masyarakat,
 * teks persetujuan di halaman Daftar, dan halaman Profil berbahasa Indonesia tanpa Hapus Akun.
 */
class NavbarProfilTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string, array<int, string>}> */
    public static function peranDanMenu(): array
    {
        return [
            'Masyarakat'    => ['Masyarakat', '/beranda', ['Ajukan Pengaduan', 'Status Pengaduan', 'Riwayat Pengaduan']],
            'Analis'        => ['Analis', '/dashboard-analis', ['Dashboard', 'Pemantauan', 'Riwayat']],
            'Kepala Bidang' => ['Kepala Bidang', '/dashboard-kepala-bidang', ['Dashboard', 'Laporan', 'Arsip Kasus']],
        ];
    }

    #[DataProvider('peranDanMenu')]
    public function test_login_diarahkan_ke_halaman_awal_peran(string $peran, string $halamanAwal): void
    {
        $user = User::factory()->create(['role' => $peran, 'password' => Hash::make('rahasia123')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect($halamanAwal);
    }

    #[DataProvider('peranDanMenu')]
    public function test_logo_mengarah_ke_halaman_awal_peran(string $peran, string $halamanAwal): void
    {
        $this->actingAs(User::factory()->create(['role' => $peran]))
            ->get($halamanAwal)
            ->assertOk()
            ->assertSee('<a href="' . url($halamanAwal) . '" class="flex items-center space-x-2 group">', false);
    }

    #[DataProvider('peranDanMenu')]
    public function test_menu_hp_berisi_menu_yang_sama_dengan_desktop(string $peran, string $halamanAwal, array $menu): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => $peran]))
            ->get($halamanAwal)
            ->assertOk()
            ->assertSee('id="tombol-menu-hp"', false)
            ->assertSee(LokerLensCopy::NAV_BUKA_MENU)
            ->getContent();

        // Ambil teks tautan di menu desktop dan di menu HP, lalu bandingkan
        $ambilMenu = function (string $penanda) use ($html): array {
            $mulai = strpos($html, $penanda);
            $this->assertNotFalse($mulai, "Penanda {$penanda} tidak ditemukan");
            $potongan = substr($html, $mulai);
            $potongan = substr($potongan, 0, strpos($potongan, '</div>'));
            preg_match_all('#<a [^>]*>\s*<span>([^<]+)</span>#', $potongan, $hasil);

            return array_map('trim', $hasil[1]);
        };

        $desktop = $ambilMenu('<div class="hidden md:flex items-center space-x-1">');
        $hp      = $ambilMenu('<div id="menu-hp"');

        $this->assertSame($menu, $desktop);
        $this->assertSame($desktop, $hp);

        // Menu desktop ditulis lebih dulu, supaya BDD ("I follow Dashboard") mengklik tautan desktop
        $this->assertLessThan(strpos($html, 'id="menu-hp"'), strpos($html, 'hidden md:flex items-center space-x-1'));
    }

    #[DataProvider('peranDanMenu')]
    public function test_ikon_lonceng_tidak_tampil(string $peran, string $halamanAwal): void
    {
        $this->actingAs(User::factory()->create(['role' => $peran]))
            ->get($halamanAwal)
            ->assertOk()
            ->assertDontSee('aria-label="Notifikasi"', false);
    }

    public function test_dropdown_profil_berisi_tautan_profil_dan_keluar(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Analis']))
            ->get('/dashboard-analis')
            ->assertSee(route('profile.edit'), false)
            ->assertSeeInOrder([LokerLensCopy::NAV_PROFIL, LokerLensCopy::NAV_KELUAR]);
    }

    public function test_footer_masyarakat_tanpa_tautan_tidak_berfungsi(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'Masyarakat']))
            ->get('/beranda')
            ->assertOk()
            ->assertDontSee('Panduan Pengaduan')
            ->assertDontSee('Cara Kerja')
            ->assertDontSee('Pusat Bantuan')
            ->assertDontSee('Fitur Sprint 2')
            ->getContent();

        // Ketiga tautan Layanan memakai gaya yang sama
        $footer = substr($html, strpos($html, '<footer'));
        $gaya = 'class="text-sm text-gray-300 hover:text-teal-400 transition"';
        foreach ([route('periksa-lowongan.index'), route('status-pengaduan.index'), route('riwayat-pengaduan.index')] as $url) {
            $this->assertStringContainsString('<a href="' . $url . '" ' . $gaya, $footer);
        }
    }

    public function test_halaman_daftar_tanpa_tautan_kosong_dan_persetujuan_tetap_wajib(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee(LokerLensCopy::DAFTAR_PERSETUJUAN)
            ->assertDontSee('href="#"', false)
            ->assertSee('name="persetujuan"', false);

        $this->post('/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@contoh.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('persetujuan');

        $this->assertGuest();
    }

    public function test_profil_bisa_ubah_nama_dan_menampilkan_pesan_tersimpan(): void
    {
        $user = User::factory()->create(['role' => 'Kepala Bidang']);

        $this->actingAs($user)
            ->followingRedirects()
            ->patch('/profile', ['name' => 'Nama Baru', 'email' => $user->email])
            ->assertOk()
            ->assertSee(LokerLensCopy::PROFIL_TERSIMPAN);

        $this->assertSame('Nama Baru', $user->fresh()->name);
    }

    public function test_ubah_kata_sandi_berbahasa_indonesia(): void
    {
        $user = User::factory()->create(['role' => 'Analis']);

        // Kata sandi saat ini salah: pesan tampil di halaman profil
        $this->actingAs($user)
            ->followingRedirects()
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'salah',
                'password' => 'sandiBaru123',
                'password_confirmation' => 'sandiBaru123',
            ])
            ->assertSee(LokerLensCopy::PROFIL_SANDI_LAMA_SALAH);

        // Kata sandi benar: tersimpan dan pesan berhasil tampil
        $this->actingAs($user)
            ->followingRedirects()
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'sandiBaru123',
                'password_confirmation' => 'sandiBaru123',
            ])
            ->assertSee(LokerLensCopy::PROFIL_SANDI_TERSIMPAN);

        $this->assertTrue(Hash::check('sandiBaru123', $user->fresh()->password));
    }
}
