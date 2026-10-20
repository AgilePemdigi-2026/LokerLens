<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambang Batas Prioritas Tinggi (BR-05)
    |--------------------------------------------------------------------------
    |
    | Probabilitas penipuan (0–100) minimal agar lowongan ditandai prioritas
    | tinggi dan masuk antrean Analis. Di bawah nilai ini kasus langsung
    | berstatus "Selesai - Risiko Rendah". Nilai final masih TBD di SRS
    | (Appendix C no. 18), sementara default 70.
    |
    */

    'ambang_prioritas' => (float) env('LOKERLENS_AMBANG_PRIORITAS', 70),

];
