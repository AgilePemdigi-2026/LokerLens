<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\User;
use Database\Seeders\LokerLensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class BladeKomponenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LokerLensSeeder::class);
    }

    /**
     * Test komponen <x-badge-status>.
     */
    public function test_komponen_badge_status_menampilkan_semua_status(): void
    {
        foreach (StatusKasus::cases() as $status) {
            $rendered = Blade::render('<x-badge-status :status="$status" />', ['status' => $status]);
            $this->assertStringContainsString($status->value, $rendered);
        }

        // Test status sebagai string biasa
        $renderedString = Blade::render('<x-badge-status status="Perlu Tinjauan" />');
        $this->assertStringContainsString('Perlu Tinjauan', $renderedString);
        $this->assertStringContainsString('bg-amber-100', $renderedString);

        // Test status Selesai
        $renderedSelesai = Blade::render('<x-badge-status :status="\App\Enums\StatusKasus::Selesai" />');
        $this->assertStringContainsString('bg-emerald-100', $renderedSelesai);
    }

    /**
     * Test komponen <x-kartu-skor>.
     */
    public function test_komponen_kartu_skor_tampil_mode_bar_dan_lingkaran(): void
    {
        // Mode Bar (default)
        $renderedBar = Blade::render('<x-kartu-skor :skor="92" label="Probabilitas Penipuan" />');
        $this->assertStringContainsString('92%', $renderedBar);
        $this->assertStringContainsString('Probabilitas Penipuan', $renderedBar);
        $this->assertStringContainsString('Kritis', $renderedBar);
        $this->assertStringContainsString('role="progressbar"', $renderedBar);

        // Mode Lingkaran (circle / lingkaran)
        $renderedCircle = Blade::render('<x-kartu-skor :skor="45" tipe="circle" label="Confidence Score" />');
        $this->assertStringContainsString('45%', $renderedCircle);
        $this->assertStringContainsString('Confidence Score', $renderedCircle);
        $this->assertStringContainsString('Risiko Sedang', $renderedCircle);
        $this->assertStringContainsString('<svg', $renderedCircle);
        $this->assertStringContainsString('stroke-dashoffset', $renderedCircle);
    }

    /**
     * Test komponen <x-chip-kata-pemicu>.
     */
    public function test_komponen_chip_kata_pemicu_tampil_chip_dan_card(): void
    {
        // Mode Chip ringkas
        $renderedChip = Blade::render('<x-chip-kata-pemicu kata="Biaya akomodasi" :bobot="25" kategori="Biaya Fiktif" />');
        $this->assertStringContainsString('Biaya akomodasi', $renderedChip);
        $this->assertStringContainsString('+25%', $renderedChip);
        $this->assertStringContainsString('Biaya Fiktif', $renderedChip);

        // Mode Card detail
        $renderedCard = Blade::render('<x-chip-kata-pemicu kata="Transfer sebelum kerja" :bobot="20" kategori="Biaya Fiktif" tipe="card" />');
        $this->assertStringContainsString('Transfer sebelum kerja', $renderedCard);
        $this->assertStringContainsString('Bobot: +20%', $renderedCard);
        $this->assertStringContainsString('Biaya Fiktif', $renderedCard);
        $this->assertStringContainsString('!', $renderedCard);
    }

    /**
     * Test komponen <x-tabel-kasus>.
     */
    public function test_komponen_tabel_kasus_untuk_analis_dan_kepala_bidang(): void
    {
        $analis = User::where('role', 'Analis')->firstOrFail();
        $kepalaBidang = User::where('role', 'Kepala Bidang')->firstOrFail();

        $kasusList = HasilKlasifikasi::with(['pengaduan.lowonganKerja.perusahaan'])->limit(3)->get();

        // Tabel untuk Analis
        $viewAnalis = $this->actingAs($analis)->view('components.tabel-kasus', [
            'kasus' => $kasusList,
            'peran' => 'analis',
        ]);
        $viewAnalis->assertSee('ID LOWONGAN')
            ->assertSee('CONFIDENCE SCORE')
            ->assertSee('STATUS SISTEM')
            ->assertSee('Lihat Detail');

        // Tabel untuk Kepala Bidang
        $pengaduanList = Pengaduan::with(['lowonganKerja.perusahaan', 'hasilKlasifikasi'])->limit(3)->get();
        $viewKb = $this->actingAs($kepalaBidang)->view('components.tabel-kasus', [
            'kasus' => $pengaduanList,
            'peran' => 'kepala-bidang',
        ]);
        $viewKb->assertSee('ID LAPORAN')
            ->assertSee('SKOR KECURIGAAN')
            ->assertSee('STATUS KASUS');

        // Empty state Analis
        $viewEmpty = $this->actingAs($analis)->view('components.tabel-kasus', [
            'kasus' => [],
            'peran' => 'analis',
        ]);
        $viewEmpty->assertSee('Data Lowongan Tidak Ditemukan');
    }

    /**
     * Test komponen <x-field-form>.
     */
    public function test_komponen_field_form_menampilkan_input_label_dan_error(): void
    {
        // Input text biasa dengan label dan placeholder
        $renderedText = Blade::render('<x-field-form name="nama_perusahaan" label="Nama Perusahaan" required placeholder="Contoh: PT ABC" />');
        $this->assertStringContainsString('Nama Perusahaan', $renderedText);
        $this->assertStringContainsString('name="nama_perusahaan"', $renderedText);
        $this->assertStringContainsString('placeholder="Contoh: PT ABC"', $renderedText);
        $this->assertStringContainsString('*', $renderedText);

        // Input textarea dengan rows
        $renderedTextarea = Blade::render('<x-field-form name="deskripsi_lowongan" label="Deskripsi" type="textarea" rows="6" hint="Minimal 50 karakter" />');
        $this->assertStringContainsString('<textarea', $renderedTextarea);
        $this->assertStringContainsString('name="deskripsi_lowongan"', $renderedTextarea);
        $this->assertStringContainsString('rows="6"', $renderedTextarea);
        $this->assertStringContainsString('Minimal 50 karakter', $renderedTextarea);

        // Input select dengan options
        $options = ['opsi1' => 'Pilihan 1', 'opsi2' => 'Pilihan 2'];
        $renderedSelect = Blade::render('<x-field-form name="kategori" label="Kategori" type="select" :options="$options" />', ['options' => $options]);
        $this->assertStringContainsString('<select', $renderedSelect);
        $this->assertStringContainsString('value="opsi1"', $renderedSelect);
        $this->assertStringContainsString('Pilihan 1', $renderedSelect);

        // Pesan error validasi
        $renderedError = Blade::render('<x-field-form name="email" label="Email" error="Format email tidak valid" />');
        $this->assertStringContainsString('Format email tidak valid', $renderedError);
        $this->assertStringContainsString('border-risk-high', $renderedError);
    }
}
