<?php

namespace Tests\Feature;

use App\Models\BuktiPendukung;
use App\Models\LowonganKerja;
use App\Models\Pengaduan;
use App\Models\Perusahaan;
use App\Models\TindakLanjutKasus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadBuktiTest extends TestCase
{
    use RefreshDatabase;

    private const JPEG_MINIMAL = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";

    private function buatKasusTindaklanjuti(User $kepalaBidang): Pengaduan
    {
        $lowongan = LowonganKerja::create([
            'id_perusahaan'      => Perusahaan::create(['nama_perusahaan' => 'PT Contoh'])->id_perusahaan,
            'posisi_lowongan'    => 'Staff Administrasi',
            'deskripsi_lowongan' => 'Deskripsi lowongan contoh.',
        ]);

        $pengaduan = Pengaduan::create([
            'id_lowongan' => $lowongan->id_lowongan,
            'id_pengguna' => User::factory()->create(['role' => 'Masyarakat'])->id,
            'nomor_tiket' => Pengaduan::generateNomorTiket(),
        ]);

        TindakLanjutKasus::create([
            'id_pengaduan' => $pengaduan->id_pengaduan,
            'id_pengguna'  => $kepalaBidang->id,
            'keputusan'    => 'Tindaklanjuti',
        ]);

        return $pengaduan;
    }

    public function test_bukti_jpeg_disimpan_sebagai_jpg(): void
    {
        Storage::fake('public');
        $kepalaBidang = User::factory()->create(['role' => 'Kepala Bidang']);
        $pengaduan = $this->buatKasusTindaklanjuti($kepalaBidang);

        $response = $this->actingAs($kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi'         => 'Draf kronologi sementara',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'bukti_tambahan'    => [
                UploadedFile::fake()->createWithContent('bukti.jpeg', self::JPEG_MINIMAL),
                UploadedFile::fake()->createWithContent('BUKTI2.JPG', self::JPEG_MINIMAL),
            ],
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['jpg', 'jpg'], BuktiPendukung::orderBy('id_bukti')->pluck('tipe_file')->all());
        $this->assertSame('bukti.jpeg', BuktiPendukung::first()->nama_file);
    }

    public function test_bukti_selain_pdf_jpg_png_ditolak(): void
    {
        Storage::fake('public');
        $kepalaBidang = User::factory()->create(['role' => 'Kepala Bidang']);
        $pengaduan = $this->buatKasusTindaklanjuti($kepalaBidang);

        $response = $this->actingAs($kepalaBidang)->post("/buat-surat/{$pengaduan->id_pengaduan}/draft", [
            'kronologi'         => 'Draf kronologi sementara',
            'tujuan_kepolisian' => 'Satreskrim Polres Wilayah Setempat',
            'bukti_tambahan'    => [UploadedFile::fake()->create('catatan.docx', 10)],
        ]);

        $response->assertSessionHasErrors('bukti_tambahan.0');
        $this->assertSame(0, BuktiPendukung::count());
    }
}
