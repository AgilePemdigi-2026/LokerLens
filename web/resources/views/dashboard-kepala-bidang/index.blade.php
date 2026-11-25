<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- Header Dashboard --}}
        <x-judul-halaman label="RINGKASAN KASUS" :judul="\App\Constants\LokerLensCopy::JUDUL_DASHBOARD_KEPALA_BIDANG" :deskripsi="\App\Constants\LokerLensCopy::DESKRIPSI_DASHBOARD_KEPALA_BIDANG" />

        {{-- Pengingat draf surat yang belum diterbitkan (issue #8) --}}
        @if($jumlahDraf > 0)
            <div id="pengingat-draf-surat" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50 border border-amber-300 rounded-card px-5 py-3 text-sm text-amber-900">
                <p class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span><strong>{{ $jumlahDraf }}</strong> {{ \App\Constants\LokerLensCopy::PENGINGAT_DRAF_SURAT }}</span>
                </p>
                <x-tombol ukuran="sm" :href="route('surat.index', ['status' => 'Draft'])" class="self-start sm:self-auto">
                    {{ \App\Constants\LokerLensCopy::BTN_LIHAT_DRAF_SURAT }} →
                </x-tombol>
            </div>
        @endif

        {{-- Ringkasan Status --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white">
                    Ringkasan Status Laporan
                </h2>

                <span class="text-xs text-gray-400">
                    Data laporan saat ini
                </span>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($ringkasan as $item)
                    @php
                        $perluTindakan = $item['status']->value === 'Menunggu Keputusan' && $item['jumlah'] > 0;
                    @endphp

                    <x-kartu-angka :label="$item['status']->value" :angka="$item['jumlah']" :nada="\App\Support\WarnaBadge::status($item['status'])"
                        :href="route('tindak-lanjut.index', ['status' => $item['status']->value])">
                        <p class="text-xs text-gray-500 mt-1">
                            kasus
                            @if($perluTindakan)
                                <x-badge :nada="\App\Support\WarnaBadge::MERAH" class="sm:ml-2">Perlu tindakan</x-badge>
                            @endif
                        </p>
                        <p class="mt-4 pt-3 border-t border-gray-100 text-xs font-semibold text-teal-700">Lihat laporan</p>
                    </x-kartu-angka>
                @endforeach
            </div>
        </div>
        {{-- Daftar Menunggu Keputusan --}}
        <div class="bg-white rounded-card border border-gray-100 shadow-lg p-6 sm:p-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-navy-900">
                    Menunggu keputusan terbaru
                </h2>

                <a href="{{ route('tindak-lanjut.index', ['status' => 'Menunggu Keputusan']) }}"
                   class="text-xs font-semibold text-teal-700 hover:text-teal-900 transition-colors">
                    Lihat semua
                </a>
            </div>

            @forelse($menungguKeputusan as $pengaduan)
                @php
                    $klasifikasi = $pengaduan->hasilKlasifikasi;
                    $hariMenunggu = $pengaduan->tanggal_pengaduan
                        ? (int) $pengaduan->tanggal_pengaduan->diffInDays(now())
                        : null;
                @endphp
                <div class="flex items-center justify-between gap-4 py-3 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-navy-900 truncate">
                            {{ $pengaduan->lowonganKerja->posisi_lowongan ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $pengaduan->lowonganKerja->perusahaan->nama_perusahaan ?? '-' }}
                            &middot; {{ $pengaduan->nomor_tiket }}
                           &middot; Dilaporkan {{ $pengaduan->tanggal_pengaduan?->translatedFormat('d M Y') }}
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2 shrink-0">
                        @if($hariMenunggu !== null)
                            <span class="text-xs text-gray-500">
                                Menunggu {{ $hariMenunggu }} hari
                            </span>
                        @endif
                        {{-- Warna skor = tingkat risiko, sama dengan halaman lain --}}
                        <x-badge :nada="$klasifikasi ? \App\Support\WarnaBadge::risiko($klasifikasi->tingkat_risiko) : \App\Support\WarnaBadge::ABU">
                            {{ $klasifikasi ? 'Skor ' . number_format((float) $klasifikasi->confidence_score, 0) . '%' : 'Skor -' }}
                        </x-badge>
                    </div>
                </div>
            @empty
                <x-pesan-kosong :kartu="false" judul="Tidak ada laporan yang menunggu keputusan." />
            @endforelse
        </div>

        {{-- Keputusan Terbaru --}}
        <div class="bg-white rounded-card border border-gray-100 shadow-lg p-6 sm:p-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-navy-900">
                    Keputusan terbaru
                </h2>
            </div>

            @forelse($keputusanTerbaru as $pengaduan)
                <div class="flex items-center justify-between gap-4 py-3 {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-navy-900 truncate">
                            {{ $pengaduan->lowonganKerja->posisi_lowongan ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $pengaduan->lowonganKerja->perusahaan->nama_perusahaan ?? '-' }}
                            &middot; {{ $pengaduan->nomor_tiket }}
                            &middot; Dilaporkan {{ $pengaduan->tanggal_pengaduan?->translatedFormat('d M Y') }}
                        </p>
                    </div>

                    <x-badge-status :status="$pengaduan->status_pengaduan" class="shrink-0" />
                </div>
            @empty
                <x-pesan-kosong :kartu="false" judul="Belum ada keputusan yang ditetapkan." />
            @endforelse
        </div>

    </div>
</x-app-layout>