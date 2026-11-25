@use('App\Constants\LokerLensCopy')
@use('App\Support\ProgresPengaduan')
@use('App\Support\WarnaBadge')

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

        <x-judul-halaman :label="LokerLensCopy::STATUS_HERO_LABEL" :deskripsi="LokerLensCopy::STATUS_HERO_DESKRIPSI">
            <x-slot:judul>
                {{ LokerLensCopy::STATUS_HERO_JUDUL }}<br>
                <span class="font-serif italic text-teal-300">{{ LokerLensCopy::STATUS_HERO_JUDUL_MIRING }}</span>
            </x-slot:judul>
        </x-judul-halaman>

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
                            <x-pil-filter :href="route('status-pengaduan.index', $kunci === ProgresPengaduan::FILTER_SEMUA ? [] : ['filter' => $kunci])"
                                :aktif="$filter === $kunci" :aria-current="$filter === $kunci ? 'page' : null">
                                {{ $labelFilter }} ({{ $jumlahPerFilter[$kunci] }})
                            </x-pil-filter>
                        @endforeach
                    </nav>
                @endif
            </div>

            @if(! $adaPengaduan)
                <!-- Tampilan kosong: tidak ada pengaduan yang belum selesai -->
                <x-pesan-kosong :judul="LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN" :keterangan="LokerLensCopy::BELUM_ADA_PENGADUAN_BERJALAN_KETERANGAN">
                    <x-tombol :href="route('periksa-lowongan.index')">{{ LokerLensCopy::BTN_PERIKSA_LOWONGAN }}</x-tombol>
                    {{-- Tombol Riwayat hanya muncul jika fitur Riwayat Pengaduan (#12) sudah ada --}}
                    @if(Route::has('riwayat-pengaduan.index'))
                        <x-tombol varian="sekunder" :href="route('riwayat-pengaduan.index')">{{ LokerLensCopy::BTN_RIWAYAT_PENGADUAN }}</x-tombol>
                    @endif
                </x-pesan-kosong>
            @elseif($daftar->isEmpty())
                <x-pesan-kosong :judul="LokerLensCopy::TIDAK_ADA_PENGADUAN_FILTER" />
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
                                <x-nomor-tiket>{{ $item->nomor_tiket }}</x-nomor-tiket>
                                {{-- Warna mengikuti teks kelompok (Menunggu/Aktif) --}}
                                <x-badge :nada="WarnaBadge::kelompok($progresItem->kelompok)" :titik="true">{{ $progresItem->labelKelompok }}</x-badge>
                            </div>

                            <h3 class="text-lg font-bold text-navy-900 mt-3 leading-snug">
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
