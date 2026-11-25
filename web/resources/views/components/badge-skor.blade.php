@props([
    'klasifikasi' => null, // HasilKlasifikasi
])

{{--
    Badge skor risiko untuk tabel (issue #25): "83%" berwarna sesuai tingkat risiko,
    dengan keterangan "Risiko tinggi/sedang/rendah" di bawahnya.
    Aturan tingkat risiko ada di HasilKlasifikasi::tingkat_risiko (<50 rendah, 50–69 sedang, ≥70 tinggi).
--}}
@if($klasifikasi)
    <x-badge :nada="\App\Support\WarnaBadge::risiko($klasifikasi->tingkat_risiko)">{{ number_format($klasifikasi->confidence_score, 0) }}%</x-badge>
    <div class="text-[10px] text-gray-500 mt-0.5 whitespace-nowrap">{{ $klasifikasi->label_risiko }}</div>
@else
    <span class="text-xs text-gray-400">-</span>
@endif
