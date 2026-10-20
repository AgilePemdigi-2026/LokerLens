<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai ditulis langsung (bukan dari App\Enums\StatusKasus) supaya migration
     * tetap bermakna sama walaupun enum PHP berubah di kemudian hari.
     */
    private const STATUS_LAMA = [
        'Perlu Tinjauan Segera',
        'Sedang Ditinjau',
        'Menunggu Keputusan',
        'Tindak Lanjut Disetujui',
        'Kasus terdokumentasi otomatis',
        'Kasus selesai',
        'Tidak dilanjuti',
    ];

    private const STATUS_BARU = [
        'Selesai - Risiko Rendah',
        'Perlu Tinjauan',
        'Sedang Ditinjau',
        'Tidak Terbukti',
        'Menunggu Keputusan',
        'Tindak Lanjut Disetujui',
        'Tidak Ditindaklanjuti',
        'Selesai',
    ];

    /** Pemetaan label lama → label baru (nilai lain tidak berubah). */
    private const PETA_LAMA_KE_BARU = [
        'Perlu Tinjauan Segera'         => 'Perlu Tinjauan',
        'Kasus terdokumentasi otomatis' => 'Selesai',
        'Kasus selesai'                 => 'Selesai',
        'Tidak dilanjuti'               => 'Tidak Ditindaklanjuti',
    ];

    /**
     * Run the migrations.
     * Menyesuaikan status_pengaduan dengan alur status keputusan tim, dan
     * memasang kunci alternatif id_tindaklanjut pada surat sesuai PDM SRS
     * (satu tindak lanjut hanya punya satu surat).
     */
    public function up(): void
    {
        $this->gantiEnumStatus(array_values(array_unique([...self::STATUS_LAMA, ...self::STATUS_BARU])), 'Perlu Tinjauan');

        foreach (self::PETA_LAMA_KE_BARU as $lama => $baru) {
            DB::table('pengaduan')->where('status_pengaduan', $lama)->update(['status_pengaduan' => $baru]);
        }

        $this->gantiEnumStatus(self::STATUS_BARU, 'Perlu Tinjauan');

        Schema::table('surat_teguran_laporan_polisi', function (Blueprint $table) {
            $table->unique('id_tindaklanjut');
        });
    }

    /**
     * Reverse the migrations.
     * "Selesai - Risiko Rendah" dan "Tidak Terbukti" tidak punya padanan lama,
     * sehingga dikembalikan ke "Kasus selesai" dan "Tidak dilanjuti".
     */
    public function down(): void
    {
        Schema::table('surat_teguran_laporan_polisi', function (Blueprint $table) {
            $table->dropUnique(['id_tindaklanjut']);
        });

        $this->gantiEnumStatus(array_values(array_unique([...self::STATUS_LAMA, ...self::STATUS_BARU])), 'Perlu Tinjauan Segera');

        $petaBaruKeLama = [
            'Perlu Tinjauan'          => 'Perlu Tinjauan Segera',
            'Selesai'                 => 'Kasus selesai',
            'Selesai - Risiko Rendah' => 'Kasus selesai',
            'Tidak Ditindaklanjuti'   => 'Tidak dilanjuti',
            'Tidak Terbukti'          => 'Tidak dilanjuti',
        ];

        foreach ($petaBaruKeLama as $baru => $lama) {
            DB::table('pengaduan')->where('status_pengaduan', $baru)->update(['status_pengaduan' => $lama]);
        }

        $this->gantiEnumStatus(self::STATUS_LAMA, 'Perlu Tinjauan Segera');
    }

    /**
     * @param  array<int, string>  $nilai
     */
    private function gantiEnumStatus(array $nilai, string $default): void
    {
        Schema::table('pengaduan', function (Blueprint $table) use ($nilai, $default) {
            $table->enum('status_pengaduan', $nilai)->default($default)->change();
        });
    }
};
