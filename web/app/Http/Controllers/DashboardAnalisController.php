<?php

namespace App\Http\Controllers;

use App\Constants\LokerLensCopy;
use App\Enums\StatusKasus;
use App\Models\HasilKlasifikasi;
use App\Models\Pengaduan;
use App\Models\TinjauanAnalis;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard Analis (Sprint 2).
 * Rute: /dashboard-analis. Peran: Analis.
 * Semua angka dihitung dalam periode terpilih (7 hari, 30 hari, atau kustom)
 * berdasarkan tanggal pengaduan.
 */
class DashboardAnalisController extends Controller
{
    /** Batas bawah skor "Sedang" (sama dengan TinjauLowonganController). */
    private const BATAS_SEDANG = 50;

    /** Rentang kustom dibatasi agar grafik tren tidak terlalu panjang. */
    private const MAKS_HARI_KUSTOM = 366;

    /** Jumlah baris antrean prioritas yang ditampilkan di dashboard. */
    private const BARIS_ANTREAN = 4;

    public function index(Request $request): View
    {
        [$periode, $dari, $sampai] = $this->tentukanPeriode($request);

        // Periode kustom diminta tapi tanggalnya kosong/tidak valid: tampilkan pesan di dekat isian tanggal
        $pesanPeriode = $request->query('periode') === 'kustom' && $periode !== 'kustom'
            ? LokerLensCopy::PERIODE_KUSTOM_TIDAK_VALID
            : null;

        $ambang = (float) config('lokerlens.ambang_prioritas');
        $tahapAnalis = array_map(fn (StatusKasus $s) => $s->value, StatusKasus::tahapAnalis());

        // ── 01 Ringkasan Tinjauan ───────────────────────────────────────
        // DB::table dipakai (bukan model) agar status tetap berupa teks biasa.
                // Hanya pengaduan yang sudah punya hasil klasifikasi (yang bisa ditinjau Analis),
        // supaya angka kartu cocok dengan antrean Tinjau Lowongan.
        $jumlahStatus = DB::table('pengaduan')
            ->join('hasil_klasifikasi', 'hasil_klasifikasi.id_pengaduan', '=', 'pengaduan.id_pengaduan')
            ->whereBetween('pengaduan.tanggal_pengaduan', [$dari, $sampai])
            ->select('pengaduan.status_pengaduan', DB::raw('COUNT(*) as total'))
            ->groupBy('pengaduan.status_pengaduan')
            ->pluck('total', 'status_pengaduan');

        $selesaiDitinjau = TinjauanAnalis::whereBetween('tanggal_tinjauan', [$dari, $sampai])->count();

        $ringkasan = [
            ['label' => 'Perlu Tinjauan',     'jumlah' => (int) ($jumlahStatus[StatusKasus::PerluTinjauan->value] ?? 0)],
            ['label' => 'Sedang Ditinjau',    'jumlah' => (int) ($jumlahStatus[StatusKasus::SedangDitinjau->value] ?? 0)],
            ['label' => 'Menunggu Keputusan', 'jumlah' => (int) ($jumlahStatus[StatusKasus::MenungguKeputusan->value] ?? 0)],
            ['label' => 'Selesai Ditinjau',   'jumlah' => $selesaiDitinjau],
        ];

        // ── 02 Distribusi Skor Risiko ───────────────────────────────────
        $skor = DB::table('hasil_klasifikasi')
            ->join('pengaduan', 'pengaduan.id_pengaduan', '=', 'hasil_klasifikasi.id_pengaduan')
            ->whereBetween('pengaduan.tanggal_pengaduan', [$dari, $sampai])
            ->pluck('hasil_klasifikasi.confidence_score')
            ->map(fn ($nilai) => (float) $nilai);

        $distribusi = [
            'rendah' => $skor->filter(fn ($s) => $s < self::BATAS_SEDANG)->count(),
            'sedang' => $skor->filter(fn ($s) => $s >= self::BATAS_SEDANG && $s < $ambang)->count(),
            'tinggi' => $skor->filter(fn ($s) => $s >= $ambang)->count(),
        ];
        $totalSkor = $skor->count();

        // ── 03 Tren Pengaduan (jumlah per hari, hari kosong = 0) ────────
        $perHari = Pengaduan::whereBetween('tanggal_pengaduan', [$dari, $sampai])
            ->pluck('tanggal_pengaduan')
            ->countBy(fn ($tanggal) => $tanggal->format('Y-m-d'));

        $tren = collect(CarbonPeriod::create($dari->copy()->startOfDay(), $sampai->copy()->startOfDay())->toArray())
            ->map(fn (Carbon $hari) => [
                'tanggal' => $hari,
                'jumlah'  => (int) ($perHari[$hari->format('Y-m-d')] ?? 0),
            ]);

        // ── 04 Antrean Prioritas Tinggi ─────────────────────────────────
        $queryAntrean = HasilKlasifikasi::where('status_prioritas', true)
            ->whereHas('pengaduan', fn ($q) => $q
                ->whereIn('status_pengaduan', $tahapAnalis)
                ->whereBetween('tanggal_pengaduan', [$dari, $sampai]));

        $totalAntrean = (clone $queryAntrean)->count();

        $antrean = $queryAntrean
            ->with('pengaduan.lowonganKerja.perusahaan')
            ->orderByDesc('confidence_score')
            ->orderByDesc('tanggal_klasifikasi')
            ->limit(self::BARIS_ANTREAN)
            ->get();

        $terakhirDiperbarui = now();

        return view('dashboard-analis.index', compact(
            'periode', 'pesanPeriode', 'dari', 'sampai', 'ringkasan', 'distribusi', 'totalSkor',
            'tren', 'antrean', 'totalAntrean', 'terakhirDiperbarui'
        ));
    }

    /**
     * Baca filter periode dari URL. Nilai yang tidak valid jatuh ke 7 hari.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    private function tentukanPeriode(Request $request): array
    {
        $pilihan = (string) $request->query('periode', '7');

        if ($pilihan === 'kustom') {
            $dari = $this->bacaTanggal($request->query('dari'));
            $sampai = $this->bacaTanggal($request->query('sampai'));

            if ($dari && $sampai && $dari->lte($sampai) && $dari->diffInDays($sampai) < self::MAKS_HARI_KUSTOM) {
                return ['kustom', $dari->startOfDay(), $sampai->endOfDay()];
            }

            $pilihan = '7';
        }

        $hari = $pilihan === '30' ? 30 : 7;

        return [
            (string) $hari,
            now()->subDays($hari - 1)->startOfDay(),
            now()->endOfDay(),
        ];
    }

    private function bacaTanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || $nilai === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}