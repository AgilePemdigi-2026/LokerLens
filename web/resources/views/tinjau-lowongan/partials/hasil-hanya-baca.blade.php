@use('App\Constants\LokerLensCopy')
@use('App\Enums\HasilTinjauan')

{{--
    Bagian hanya-baca untuk detail Riwayat Tinjauan (issue #25), menggantikan form tinjauan.
    Variabel dari tinjau-lowongan/detail: $pengaduan, $tinjauan.
    Isi surat dan lampiran sengaja tidak ditampilkan (hanya jenis, nomor, dan tanggal terbit).
--}}
@php
    $tindakLanjut = $pengaduan->tindakLanjutKasus;
    $surat = $tindakLanjut?->suratTeguranLaporanPolisi;
    $suratTerbit = $surat && $surat->status_penerbitan === 'Terbit';
    $tidakTerbukti = $tinjauan->hasil_tinjauan === HasilTinjauan::TidakTerbukti->value;
    $formatTanggal = fn ($tanggal) => $tanggal ? $tanggal->translatedFormat('d F Y, H:i') . ' WIB' : '-';
@endphp

<!-- 4. Hasil Tinjauan Analis (hanya baca) -->
<div id="hasil-tinjauan-analis" class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
    <h3 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-3 mb-4">
        {{ LokerLensCopy::JUDUL_HASIL_TINJAUAN }}
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm mb-4">
        <div>
            <span class="text-gray-500 block text-xs">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL }}:</span>
            <span class="inline-block mt-0.5 px-2.5 py-0.5 rounded text-xs font-semibold {{ $tidakTerbukti ? 'bg-gray-100 text-gray-700 border border-gray-200' : 'bg-red-100 text-risk-high border border-red-200' }}">
                {{ $tinjauan->hasil_tinjauan }}
            </span>
        </div>
        <div>
            <span class="text-gray-500 block text-xs">{{ LokerLensCopy::KOLOM_ANALIS }}:</span>
            <strong class="text-navy-900">{{ $tinjauan->analis->name ?? '-' }}</strong>
        </div>
        <div>
            <span class="text-gray-500 block text-xs">{{ LokerLensCopy::KOLOM_TANGGAL_DITINJAU }}:</span>
            <span class="text-gray-900">{{ $formatTanggal($tinjauan->tanggal_tinjauan) }}</span>
        </div>
        <div>
            <span class="text-gray-500 block text-xs">{{ LokerLensCopy::LABEL_STATUS_KASUS_SEKARANG }}:</span>
            <span class="inline-block mt-0.5 px-2.5 py-0.5 rounded text-xs font-semibold {{ $pengaduan->warna_badge }}">
                {{ $pengaduan->status_pengaduan }}
            </span>
        </div>
    </div>
    <div>
        <span class="text-gray-500 block text-xs mb-1">{{ LokerLensCopy::LABEL_CATATAN_TINJAUAN }}:</span>
        <div class="bg-gray-50 p-4 rounded-control border border-gray-200 text-xs sm:text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ $tinjauan->catatan_tinjauan ?: '-' }}</div>
    </div>
</div>

<!-- 5. Hasil Akhir Kasus (hanya baca) -->
<div id="hasil-akhir-kasus" class="bg-navy-900 text-white rounded-card shadow-xl p-6 sm:p-8 border border-navy-800">
    <h3 class="text-xl font-bold text-teal-400 border-b border-navy-800 pb-4 mb-5">
        {{ LokerLensCopy::JUDUL_HASIL_AKHIR_KASUS }}
    </h3>

    @if($tidakTerbukti)
        <p class="text-sm text-gray-300">{{ LokerLensCopy::KASUS_DITUTUP_TAHAP_ANALIS }}</p>
    @elseif(! $tindakLanjut)
        <p class="text-sm text-gray-300">{{ LokerLensCopy::MENUNGGU_KEPUTUSAN_KABID }}</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
            <div>
                <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_KEPUTUSAN }}:</span>
                <strong class="text-white">{{ $tindakLanjut->keputusan }}</strong>
            </div>
            <div>
                <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_KEPALA_BIDANG }}:</span>
                <strong class="text-white">{{ $tindakLanjut->kepalaBidang->name ?? '-' }}</strong>
            </div>
            <div>
                <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_TANGGAL_KEPUTUSAN }}:</span>
                <span class="text-gray-200">{{ $formatTanggal($tindakLanjut->tanggal_keputusan) }}</span>
            </div>
            <div class="sm:col-span-2">
                <span class="text-gray-400 block text-xs mb-1">{{ LokerLensCopy::LABEL_CATATAN_KEPUTUSAN }}:</span>
                <div class="bg-navy-800 border border-navy-700 rounded-control p-3 text-gray-200 whitespace-pre-line break-words">{{ $tindakLanjut->catatan_keputusan ?: '-' }}</div>
            </div>

            @if($suratTerbit)
                {{-- Hanya identitas surat; isi surat dan lampiran tidak ditampilkan --}}
                <div>
                    <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_JENIS_DOKUMEN }}:</span>
                    <strong class="text-white">{{ $surat->jenis_dokumen }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_NOMOR_SURAT }}:</span>
                    <strong class="text-white font-mono">{{ $surat->nomor_surat ?: '-' }}</strong>
                </div>
                <div>
                    <span class="text-gray-400 block text-xs">{{ LokerLensCopy::LABEL_TANGGAL_TERBIT }}:</span>
                    <span class="text-gray-200">{{ $formatTanggal($surat->tanggal_terbit) }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="pt-5 mt-5 border-t border-navy-800">
        <a href="{{ route('riwayat-tinjauan.index') }}" class="inline-block px-5 py-2.5 rounded-control border border-navy-700 text-gray-300 hover:bg-navy-800 text-xs font-semibold transition">
            {{ LokerLensCopy::BTN_KEMBALI_RIWAYAT_TINJAUAN }}
        </a>
    </div>
</div>
