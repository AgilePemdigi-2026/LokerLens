<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambang Batas Prioritas Tinggi (BR-05)
    |--------------------------------------------------------------------------
    |
    | Probabilitas penipuan (0–100) minimal agar lowongan ditandai prioritas
    | tinggi dan masuk antrean Analis. Di bawah nilai ini kasus langsung
    | berstatus "Selesai - Risiko Rendah". Disepakati tim: 70. Nilai ini
    | harus sama dengan "ambang_prioritas_tinggi" (0.7) di
    | ml/models/model_lokerlens_meta.json.
    |
    */

    'ambang_prioritas' => (float) env('LOKERLENS_AMBANG_PRIORITAS', 70),

    /*
    |--------------------------------------------------------------------------
    | Mode Preview Pengembangan (/dev)
    |--------------------------------------------------------------------------
    |
    | Halaman /dev untuk login cepat dengan akun demo dan membuka semua
    | halaman. Route-nya hanya didaftarkan jika APP_ENV=local DAN
    | DEV_PREVIEW=true. Jangan pernah diaktifkan di server sungguhan.
    |
    */

    'dev_preview' => (bool) env('DEV_PREVIEW', false),

];
