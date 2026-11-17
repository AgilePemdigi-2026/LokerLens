@props([
    'klasifikasi' => null, // HasilKlasifikasi
])

{{--
    Badge skor risiko untuk tabel (issue #25): "83%" berwarna sesuai tingkat risiko,
    dengan keterangan "Risiko tinggi/sedang/rendah" di bawahnya.
    Aturan tingkat risiko ada di HasilKlasifikasi::tingkat_risiko (<50 rendah, 50–69 sedang, ≥70 tinggi).
--}}
@if($klasifikasi)
    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-bold {{ $klasifikasi->warna_risiko['badge'] }}">
        {{ number_format($klasifikasi->confidence_score, 0) }}%
    </span>
    <div class="text-[10px] text-gray-400 mt-0.5 whitespace-nowrap">{{ $klasifikasi->label_risiko }}</div>
@else
    <span class="text-xs text-gray-400">-</span>
@endif
