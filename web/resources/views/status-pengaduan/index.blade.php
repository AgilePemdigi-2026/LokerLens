@use('App\Constants\LokerLensCopy')
@use('App\Support\ProgresPengaduan')

{{-- Status Pengaduan — daftar pengaduan berjalan (issue #11). Detail ada di status-pengaduan/detail.blade.php. --}}
<x-app-layout>
    @php
        // Filter dibawa ke halaman detail supaya tombol kembali membuka daftar yang sama
        $parameterFilter = $filter === ProgresPengaduan::FILTER_SEMUA ? [] : ['filter' => $filter];
    @endphp

    <div class="space-y-8 py-2">
        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-xs text-gray-400" aria-label="Breadcrumb">
            <a href="{{ route('beranda') }}" class="hover:text-teal-400 transition">Beranda</a>
            <span>/</span>
            <span class="text-white font-medium">Status Pengaduan</span>
        </nav>

        <!-- Hero ringkas -->
        <div class="bg-navy-900 border border-navy-800 rounded-2xl px-6 py-8 sm:px-10 text-white shadow-2xl grid grid-cols-1 md:grid-cols-2 gap-6 md:items-end">
            <div class="space-y-3">
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400">
                    {{ LokerLensCopy::STATUS_HERO_LABEL }}
                </p>
                <h1 class="font-serif text-3xl sm:text-4xl font-bold leading-tight">
                    {{ LokerLensCopy::STATUS_HERO_JUDUL }}<br>
                    <span class="italic text-teal-300">{{ LokerLensCopy::STATUS_HERO_JUDUL_MIRING }}</span>
                </h1>
            </div>
            <p class="text-sm text-gray-300 leading-relaxed md:max-w-sm md:justify-self-end">
                {{ LokerLensCopy::STATUS_HERO_DESKRIPSI }}
            </p>
        </div>

        <section class="space-y-5">
            <!-- Judul daftar + filter -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-mono font-bold uppercase tracking-widest text-teal-400">{{ LokerLensCopy::STATUS_LABEL_DAFTAR }}</p>
                    <h2 class="text-2xl font-bold text-white tracking-tight mt-1">{{ LokerLensCopy::STATUS_JUDUL_DAFTAR }}</h2>
                </div>

                @if($adaPengaduan)
                    <!-- Filter (query string ?filter=); jumlah per pilihan dihitung di ProgresPengaduan -->
                    <nav id="filter-pengaduan" class="flex flex-wrap gap-2" aria-label="Filter pengaduan">
                        @foreach(LokerLensCopy::FILTER_PENGADUAN as $kunci => $labelFilter)
                            <a href="{{ route('status-pengaduan.index', $kunci === ProgresPengaduan::FILTER_SEMUA ? [] : ['filter' => $kunci]) }}"
                               @if($filter === $kunci) aria-current="page" @endif
                               class="px-4 py-1.5 rounded-full text-xs font-semibold border transition {{ $filter === $kunci ? 'bg-teal-600 border-teal-600 text-white' : 'bg-navy-800 border-navy-800 text-gray-300 hover:text-white hover:border-teal-600' }}">
                                {{ $labelFilter }} ({{ $jumlahPerFilter[$kunci] }})
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>

            @if(! $adaPengaduan)
                <!-- Tampilan kosong: tidak ada pengaduan yang belum selesai -->
                <div class="bg-white rounded-card shadow-lg p-8 text-center border border-gray-100">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-base font-bold text-navy-900">{{ LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN }}</p>
                    <p class="text-xs text-gray-500 mt-1 mb-5">{{ LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN_KETERANGAN }}</p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a href="{{ route('periksa-lowongan.index') }}"
                           class="inline-flex items-center px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold shadow transition">
                            {{ LokerLensCopy::BTN_PERIKSA_LOWONGAN }}
                        </a>
                        {{-- Tombol Riwayat hanya muncul jika fitur Riwayat Pengaduan (#12) sudah ada --}}
                        @if(Route::has('riwayat-pengaduan.index'))
                            <a href="{{ route('riwayat-pengaduan.index') }}"
                               class="inline-flex items-center px-6 py-2.5 rounded-control border border-gray-300 hover:bg-gray-50 text-navy-900 text-xs font-semibold transition">
                                {{ LokerLensCopy::BTN_RIWAYAT_PENGADUAN }}
                            </a>
                        @endif
                    </div>
                </div>
            @elseif($daftar->isEmpty())
                <div class="bg-white rounded-card shadow-lg p-6 text-center text-sm font-semibold text-gray-500 border border-gray-100">
                    {{ LokerLensCopy::TIDAK_ADA_PENGADUAN_FILTER }}
                </div>
            @else
                <!-- Grid kartu: 1 kolom di HP, 2 di tablet, 3 di layar besar. Seluruh kartu bisa diklik. -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($daftar as $item)
                        @php
                            $progresItem = $item->progres();
                            $klasItem = $item->hasilKlasifikasi;
                        @endphp
                        <a href="{{ route('status-pengaduan.detail', ['id' => $item->id_pengaduan, ...$parameterFilter]) }}"
                           data-testid="kartu-pengaduan"
                           class="flex flex-col bg-white rounded-card shadow-lg p-5 border-2 border-transparent transition hover:shadow-xl hover:border-teal-600">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                    {{ $item->nomor_tiket }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $progresItem->warnaKelompok }}">
                                    {{ $progresItem->labelKelompok }}
                                </span>
                            </div>

                            <h3 class="font-serif text-lg font-bold text-navy-900 mt-3 leading-snug">
                                {{ $item->lowonganKerja->posisi_lowongan ?? '-' }}
                            </h3>
                            <p class="text-sm text-gray-600">{{ $item->lowonganKerja->perusahaan->nama_perusahaan ?? '-' }}</p>

                            <dl class="grid grid-cols-2 gap-3 mt-auto pt-4 text-xs">
                                <div class="pt-3 border-t border-gray-100">
                                    <dt class="text-gray-500">{{ LokerLensCopy::LABEL_SKOR_RISIKO }}</dt>
                                    <dd class="mt-0.5">
                                        @if($klasItem)
                                            <strong class="text-sm {{ $klasItem->warna_risiko['teks'] }}">{{ number_format($klasItem->confidence_score, 0) }}%</strong>
                                        @else
                                            <span class="font-semibold text-gray-400">{{ LokerLensCopy::SKOR_BELUM_TERSEDIA }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="pt-3 border-t border-gray-100">
                                    <dt class="text-gray-500">{{ LokerLensCopy::LABEL_PEMBARUAN_TERAKHIR }}</dt>
                                    <dd class="font-semibold text-navy-900 mt-0.5">{{ ProgresPengaduan::formatTanggal($progresItem->pembaruanTerakhir) }}</dd>
                                </div>
                            </dl>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
