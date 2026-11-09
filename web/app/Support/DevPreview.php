<?php

namespace App\Support;

use App\Enums\StatusKasus;
use App\Models\Pengaduan;

/**
 * Mode preview pengembangan (/dev): hanya untuk laptop pengembang.
 * Tidak mengubah aturan login maupun hak akses — setiap halaman tetap
 * dibuka lewat route biasa dengan middleware auth + role.
 */
class DevPreview
{
    /**
     * Aktif hanya jika APP_ENV=local DAN DEV_PREVIEW=true.
     */
    public static function aktif(): bool
    {
        return app()->environment('local') && config('lokerlens.dev_preview') === true;
    }

    /**
     * Daftar tautan halaman per kelompok peran, memakai ID kasus yang ada di database.
     *
     * @return array<string, array<int, array{label: string, url: ?string, peran: string, catatan?: string}>>
     */
    public static function tautan(): array
    {
        $kasus = fn (StatusKasus $status) => Pengaduan::with(['hasilKlasifikasi', 'tindakLanjutKasus.suratTeguranLaporanPolisi'])
            ->where('status_pengaduan', $status)
            ->orderBy('id_pengaduan')
            ->first();

        $hasilPeriksa = [];
        foreach (StatusKasus::cases() as $status) {
            $p = $kasus($status);
            $hasilPeriksa[] = [
                'label' => "Hasil Pemeriksaan — {$status->value}" . ($p ? " ({$p->nomor_tiket})" : ''),
                'url'   => $p ? route('periksa-lowongan.hasil', $p->id_pengaduan) : null,
                'peran' => 'Masyarakat',
            ];
        }

        $detailAnalis = function (StatusKasus $status, string $catatan = '') use ($kasus): array {
            $p = $kasus($status);

            return [
                'label'   => "Detail Lowongan — {$status->value}" . ($p ? " ({$p->nomor_tiket})" : ''),
                'url'     => $p?->hasilKlasifikasi ? route('tinjau-lowongan.detail', $p->hasilKlasifikasi->id_klasifikasi) : null,
                'peran'   => 'Analis',
                'catatan' => $catatan,
            ];
        };

        $halamanKepalaBidang = [['label' => 'Daftar Laporan', 'url' => route('tindak-lanjut.index'), 'peran' => 'Kepala Bidang']];
        foreach (StatusKasus::tahapKepalaBidang() as $status) {
            $p = $kasus($status);
            $halamanKepalaBidang[] = [
                'label' => "Detail Laporan — {$status->value}" . ($p ? " ({$p->nomor_tiket})" : ''),
                'url'   => $p ? route('tindak-lanjut.detail', $p->id_pengaduan) : null,
                'peran' => 'Kepala Bidang',
            ];
        }

        $disetujui = $kasus(StatusKasus::TindakLanjutDisetujui);
        $diarsipkan = $kasus(StatusKasus::TidakDitindaklanjuti);
        $selesai = $kasus(StatusKasus::Selesai);
        $suratTerbit = $selesai?->tindakLanjutKasus?->suratTeguranLaporanPolisi;

        $halamanKepalaBidang[] = [
            'label' => 'Hasil Keputusan — Tindaklanjuti' . ($disetujui ? " ({$disetujui->nomor_tiket})" : ''),
            'url'   => $disetujui ? route('tindak-lanjut.hasil', $disetujui->id_pengaduan) : null,
            'peran' => 'Kepala Bidang',
        ];
        $halamanKepalaBidang[] = [
            'label' => 'Hasil Keputusan — Tidak Ditindaklanjuti' . ($diarsipkan ? " ({$diarsipkan->nomor_tiket})" : ''),
            'url'   => $diarsipkan ? route('tindak-lanjut.hasil', $diarsipkan->id_pengaduan) : null,
            'peran' => 'Kepala Bidang',
        ];
        $halamanKepalaBidang[] = [
            'label'   => 'Form Buat Laporan Polisi' . ($disetujui ? " ({$disetujui->nomor_tiket})" : ''),
            'url'     => $disetujui ? route('buat-surat.index', $disetujui->id_pengaduan) : null,
            'peran'   => 'Kepala Bidang',
            'catatan' => 'Menekan "Terbitkan Laporan" akan menyelesaikan kasus ini.',
        ];
        $halamanKepalaBidang[] = [
            'label' => 'Hasil Laporan Polisi Terbit' . ($selesai ? " ({$selesai->nomor_tiket})" : ''),
            'url'   => $selesai && $suratTerbit ? route('buat-surat.terbit', ['pengaduanId' => $selesai->id_pengaduan, 'suratId' => $suratTerbit->id_surat]) : null,
            'peran' => 'Kepala Bidang',
        ];

        return [
            'Tamu (belum login)' => [
                ['label' => 'Login', 'url' => route('login'), 'peran' => 'Tamu'],
                ['label' => 'Registrasi', 'url' => route('register'), 'peran' => 'Tamu'],
                ['label' => 'Lupa Password', 'url' => route('password.request'), 'peran' => 'Tamu'],
            ],
            'Masyarakat' => [
                ['label' => 'Beranda', 'url' => route('beranda'), 'peran' => 'Masyarakat'],
                ['label' => 'Periksa Lowongan Kerja', 'url' => route('periksa-lowongan.index'), 'peran' => 'Masyarakat'],
                ...$hasilPeriksa,
            ],
            'Analis' => [
                ['label' => 'Daftar Lowongan Prioritas Tinggi', 'url' => route('tinjau-lowongan.index'), 'peran' => 'Analis'],
                $detailAnalis(StatusKasus::PerluTinjauan, 'Membuka halaman ini mengubah status kasus menjadi "Sedang Ditinjau".'),
                $detailAnalis(StatusKasus::SedangDitinjau),
                $detailAnalis(StatusKasus::TidakTerbukti, 'Tinjauan sudah selesai, formulir tidak ditampilkan.'),
            ],
            'Kepala Bidang' => $halamanKepalaBidang,
            'Semua peran' => [
                ['label' => 'Profil', 'url' => route('profile.edit'), 'peran' => 'Semua peran (login)'],
            ],
        ];
    }
}
