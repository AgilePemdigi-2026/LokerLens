<?php

namespace Tests\Feature;

use App\Models\BuktiPendukung;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Persiapan deploy (issue #25): seeder produksi, perintah buat akun petugas,
 * berkas bukti di disk private, dan link https di belakang proxy.
 */
class PersiapanDeployTest extends TestCase
{
    use RefreshDatabase;

    // ─── Seeder ──────────────────────────────────────────────────────────

    public function test_database_seeder_tidak_membuat_akun_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(0, Pengaduan::count());
    }

    public function test_demo_seeder_membuat_akun_demo(): void
    {
        $this->seed(DemoSeeder::class);

        foreach (LokerLensSeeder::AKUN_DEMO as $peran => $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $peran]);
        }
    }

    public function test_data_demo_ditolak_di_produksi(): void
    {
        $this->app['env'] = 'production';

        try {
            // Dipanggil langsung (seperti db:seed --force), tanpa pertanyaan konfirmasi bawaan Laravel
            (new DemoSeeder)->setContainer($this->app)->__invoke();
            $this->fail('DemoSeeder seharusnya ditolak di produksi');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('tidak boleh dijalankan di produksi', $e->getMessage());
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertSame(0, User::count());
    }

    // ─── Perintah lokerlens:buat-petugas ─────────────────────────────────

    public function test_buat_akun_petugas(): void
    {
        $this->artisan('lokerlens:buat-petugas')
            ->expectsChoice('Peran', 'Kepala Bidang', ['Analis', 'Kepala Bidang'])
            ->expectsQuestion('Nama lengkap', 'Siti Rahma')
            ->expectsQuestion('Email', 'Siti.Rahma@Disnaker.go.id')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'rahasiaKuat1')
            ->expectsQuestion('Ulangi kata sandi', 'rahasiaKuat1')
            ->expectsOutputToContain('berhasil dibuat')
            ->assertSuccessful();

        $user = User::where('email', 'siti.rahma@disnaker.go.id')->firstOrFail();
        $this->assertSame('Kepala Bidang', $user->role);
        $this->assertTrue(Hash::check('rahasiaKuat1', $user->password));

        // Akun baru bisa login dan diarahkan ke halaman awal perannya
        $this->post('/login', ['email' => 'siti.rahma@disnaker.go.id', 'password' => 'rahasiaKuat1'])
            ->assertRedirect('/dashboard-kepala-bidang');
    }

    public function test_buat_petugas_ditolak_jika_isian_salah(): void
    {
        User::factory()->create(['email' => 'sudah.ada@disnaker.go.id']);

        $this->artisan('lokerlens:buat-petugas')
            ->expectsChoice('Peran', 'Analis', ['Analis', 'Kepala Bidang'])
            ->expectsQuestion('Nama lengkap', '')
            ->expectsQuestion('Email', 'sudah.ada@disnaker.go.id')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'pendek')
            ->expectsQuestion('Ulangi kata sandi', 'beda')
            ->expectsOutputToContain('Nama lengkap wajib diisi.')
            ->expectsOutputToContain('Email sudah terdaftar.')
            ->expectsOutputToContain('Kedua kata sandi tidak sama.')
            ->expectsOutputToContain('Akun tidak dibuat')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }

    // ─── Berkas bukti di disk private ────────────────────────────────────

    public function test_bukti_disimpan_di_disk_private_dan_hanya_kepala_bidang_yang_bisa_membuka(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seed(DemoSeeder::class);
        $kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-8770')->firstOrFail();

        $this->actingAs($kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi' => 'Kronologi contoh',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'bukti_tambahan' => [UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf')],
        ])->assertSessionHasNoErrors();

        $bukti = BuktiPendukung::where('id_pengaduan', $pengaduan->id_pengaduan)->latest('id_bukti')->firstOrFail();

        // Tersimpan di disk private, bukan di folder publik
        Storage::disk('local')->assertExists($bukti->path_file);
        Storage::disk('public')->assertMissing($bukti->path_file);

        // Kepala Bidang bisa membuka lewat route yang dicek perannya
        $this->actingAs($kepalaBidang)->get(route('bukti.lihat', $bukti))->assertOk();
        $this->get(route('buat-surat.index', $pengaduan->id_pengaduan))->assertSee(route('bukti.lihat', $bukti), false);

        // Peran lain ditolak, tamu diarahkan ke login
        $this->actingAs(User::where('role', 'Analis')->firstOrFail())->get(route('bukti.lihat', $bukti))->assertForbidden();
        $this->actingAs(User::where('role', 'Masyarakat')->firstOrFail())->get(route('bukti.lihat', $bukti))->assertForbidden();
        auth()->logout();
        $this->get(route('bukti.lihat', $bukti))->assertRedirect('/login');
    }

    public function test_bukti_yang_berkasnya_hilang_mengembalikan_404(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seed(DemoSeeder::class);
        $pengaduan = Pengaduan::where('nomor_tiket', '#LKR-8770')->firstOrFail();
        $bukti = BuktiPendukung::create([
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'nama_file'    => 'hilang.pdf',
            'tipe_file'    => 'pdf',
            'ukuran_file'  => 100,
            'path_file'    => 'bukti/hilang.pdf',
        ]);

        $this->actingAs(User::where('role', 'Kepala Bidang')->firstOrFail())
            ->get(route('bukti.lihat', $bukti))
            ->assertNotFound();
    }

    // ─── https di belakang proxy ─────────────────────────────────────────

    public function test_link_memakai_https_jika_app_url_https(): void
    {
        config(['app.url' => 'https://lokerlens.contoh.go.id']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', route('login'));
        $this->assertStringStartsWith('https://', url('/beranda'));

        \Illuminate\Support\Facades\URL::forceScheme(null);
    }
}
