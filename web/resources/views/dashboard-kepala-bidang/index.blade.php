<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- Header Dashboard --}}
        <div class="relative overflow-hidden bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl">
            <div class="relative z-10">
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2">
                    RINGKASAN KASUS
                </p>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    {{ \App\Constants\LokerLensCopy::JUDUL_DASHBOARD_KEPALA_BIDANG }}
                </h1>

                <p class="text-sm text-gray-300 mt-2 max-w-2xl">
                    {{ \App\Constants\LokerLensCopy::DESKRIPSI_DASHBOARD_KEPALA_BIDANG }}
                </p>
            </div>

            <div class="absolute -right-8 -bottom-12 w-40 h-40 rounded-full border-[20px] border-teal-400/10"></div>
            <div class="absolute -right-2 -bottom-6 w-24 h-24 rounded-full border-[12px] border-teal-400/10"></div>
        </div>

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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($ringkasan as $item)
                    @php
                        $perluTindakan = $item['status']->value === 'Menunggu Keputusan' && $item['jumlah'] > 0;
                    @endphp

                    <a href="{{ route('tindak-lanjut.index', ['status' => $item['status']->value]) }}"
                       class="group bg-white rounded-card shadow-sm p-6 border hover:shadow-lg hover:-translate-y-1 transition-all duration-200 block {{ $perluTindakan ? 'border-teal-500 ring-2 ring-teal-400/60' : 'border-gray-100' }}">

                        <div class="flex items-start justify-between gap-3">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $item['status']->warnaBadge() }}">
                                {{ $item['status']->value }}
                            </span>

                            <span class="text-gray-300 group-hover:text-teal-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="w-5 h-5"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor"
                                     stroke-width="1.8">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        </div>

                        <p class="text-4xl font-extrabold text-navy-900 mt-5">
                            {{ $item['jumlah'] }}
                        </p>

                        <p class="text-sm text-gray-500 mt-1">
                            kasus
                            @if($perluTindakan)
                                <span class="ml-2 px-2 py-0.5 rounded bg-teal-50 text-teal-700 text-xs font-semibold">Perlu tindakan</span>
                            @endif
                        </p>

                        <div class="mt-5 pt-4 border-t border-gray-100">
                            <span class="text-xs font-semibold text-teal-700 group-hover:text-teal-900 transition-colors">
                                Lihat laporan
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
                {{-- Daftar Menunggu Keputusan --}}
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 sm:p-8">
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
                    $skor = $pengaduan->hasilKlasifikasi?->confidence_score;
                    $warnaSkor = $skor === null
                        ? 'bg-gray-100 text-gray-600'
                        : ($skor >= 80 ? 'bg-red-100 text-red-700' : ($skor >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-700'));
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
                            &middot; {{ $pengaduan->tanggal_pengaduan?->translatedFormat('d M Y') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($hariMenunggu !== null)
                            <span class="text-xs text-gray-500">
                                Menunggu {{ $hariMenunggu }} hari
                            </span>
                        @endif
                        <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $warnaSkor }}">
                            {{ $skor !== null ? 'Skor ' . number_format((float) $skor, 0) . '%' : 'Skor -' }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4">
                    Tidak ada laporan yang menunggu keputusan.
                </p>
            @endforelse
        </div>

        {{-- Keputusan Terbaru --}}
        <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 sm:p-8">
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
                            &middot; {{ $pengaduan->tanggal_pengaduan?->translatedFormat('d M Y') }}
                        </p>
                    </div>

                                        @php
                        $statusKasus = $pengaduan->status_pengaduan instanceof \App\Enums\StatusKasus
                            ? $pengaduan->status_pengaduan
                            : \App\Enums\StatusKasus::tryFrom((string) $pengaduan->status_pengaduan);
                    @endphp
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold shrink-0 {{ $statusKasus?->warnaBadge() ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $statusKasus?->value ?? '-' }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 py-4">
                    Belum ada keputusan yang ditetapkan.
                </p>
            @endforelse
        </div>

    </div>
</x-app-layout>