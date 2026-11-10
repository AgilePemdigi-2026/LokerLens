<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test tidak boleh memanggil API ML sungguhan. Pakai palsukanApiMl().
        Http::preventStrayRequests();
    }

    /**
     * Palsukan jawaban API ML (POST /predict) dengan probabilitas penipuan tertentu (0–1).
     *
     * @param  array<int, string>  $kataPemicu
     */
    protected function palsukanApiMl(float $probabilitas, array $kataPemicu = ['biaya pendaftaran', 'transfer']): void
    {
        Http::fake([
            '*/predict' => Http::response([
                'probabilitas_penipuan' => $probabilitas,
                'label'                 => $probabilitas >= 0.5 ? 'palsu' : 'asli',
                'kata_pemicu'           => array_map(fn ($kata) => ['kata' => $kata, 'bobot' => 0.2], $kataPemicu),
                'versi_model'           => 'Model Uji',
            ]),
        ]);
    }
}
