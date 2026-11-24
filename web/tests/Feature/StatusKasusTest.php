<?php

namespace Tests\Feature;

use App\Enums\StatusKasus;
use Tests\TestCase;

class StatusKasusTest extends TestCase
{
    public function test_status_hanya_boleh_maju_sesuai_urutan(): void
    {
        $this->assertTrue(StatusKasus::PerluTinjauan->bolehMenjadi(StatusKasus::SedangDitinjau));
        $this->assertTrue(StatusKasus::SedangDitinjau->bolehMenjadi(StatusKasus::TidakTerbukti));
        $this->assertTrue(StatusKasus::SedangDitinjau->bolehMenjadi(StatusKasus::MenungguKeputusan));
        $this->assertTrue(StatusKasus::MenungguKeputusan->bolehMenjadi(StatusKasus::TindakLanjutDisetujui));
        $this->assertTrue(StatusKasus::MenungguKeputusan->bolehMenjadi(StatusKasus::TidakDitindaklanjuti));
        $this->assertTrue(StatusKasus::TindakLanjutDisetujui->bolehMenjadi(StatusKasus::Selesai));
    }

    public function test_status_tidak_boleh_melompat_atau_mundur(): void
    {
        $this->assertFalse(StatusKasus::PerluTinjauan->bolehMenjadi(StatusKasus::MenungguKeputusan));
        $this->assertFalse(StatusKasus::SedangDitinjau->bolehMenjadi(StatusKasus::TindakLanjutDisetujui));
        $this->assertFalse(StatusKasus::MenungguKeputusan->bolehMenjadi(StatusKasus::SedangDitinjau));
        $this->assertFalse(StatusKasus::TindakLanjutDisetujui->bolehMenjadi(StatusKasus::MenungguKeputusan));
        $this->assertFalse(StatusKasus::MenungguKeputusan->bolehMenjadi(StatusKasus::Selesai));
    }

    public function test_status_akhir_tidak_bisa_diubah(): void
    {
        $akhir = [StatusKasus::SelesaiRisikoRendah, StatusKasus::TidakTerbukti, StatusKasus::Selesai, StatusKasus::TidakDitindaklanjuti];

        foreach (StatusKasus::cases() as $status) {
            $this->assertSame(in_array($status, $akhir, true), $status->isAkhir(), $status->value);
        }

        foreach ($akhir as $asal) {
            foreach (StatusKasus::cases() as $tujuan) {
                $this->assertFalse($asal->bolehMenjadi($tujuan), "{$asal->value} → {$tujuan->value}");
            }
        }
    }

    public function test_status_awal_mengikuti_ambang_prioritas_di_config(): void
    {
        $this->assertSame(70.0, config('lokerlens.ambang_prioritas'));
        $this->assertSame(StatusKasus::PerluTinjauan, StatusKasus::awalDariSkor(70));
        $this->assertSame(StatusKasus::SelesaiRisikoRendah, StatusKasus::awalDariSkor(69.99));

        config(['lokerlens.ambang_prioritas' => 80]);
        $this->assertSame(StatusKasus::SelesaiRisikoRendah, StatusKasus::awalDariSkor(75));
    }
}
