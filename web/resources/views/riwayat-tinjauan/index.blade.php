@use('App\Constants\LokerLensCopy')
@use('App\Enums\HasilTinjauan')
@use('App\Http\Controllers\RiwayatTinjauanController')

{{-- Riwayat Tinjauan Analis (issue #25). Gaya mengikuti halaman Pemantauan (tinjau-lowongan/index). --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Header Halaman -->
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                {{ LokerLensCopy::RIWAYAT_TINJAUAN_JUDUL }}
            </h1>
            <p class="text-sm text-gray-300 mt-1">
                {{ LokerLensCopy::RIWAYAT_TINJAUAN_DESKRIPSI }}
            </p>
        </div>

        <!-- Kotak Angka: dari semua kasus yang sudah ditinjau (tanpa filter) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ LokerLensCopy::RIWAYAT_TINJAUAN_STAT_TOTAL }}</p>
                <p id="stat-total-ditinjau" class="text-3xl font-extrabold text-navy-900 mt-1">{{ $statistik['total'] }}</p>
            </div>
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ HasilTinjauan::TerkonfirmasiMencurigakan->value }}</p>
                <p id="stat-terkonfirmasi" class="text-3xl font-extrabold text-risk-high mt-1">{{ $statistik['terkonfirmasi'] }}</p>
            </div>
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ HasilTinjauan::TidakTerbukti->value }}</p>
                <p id="stat-tidak-terbukti" class="text-3xl font-extrabold text-gray-600 mt-1">{{ $statistik['tidakTerbukti'] }}</p>
            </div>
        </div>

        <!-- Kartu Utama: Daftar Lowongan yang Sudah Ditinjau -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight text-navy-900">
                    {{ LokerLensCopy::RIWAYAT_TINJAUAN_JUDUL_DAFTAR }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ LokerLensCopy::RIWAYAT_TINJAUAN_KETERANGAN_DAFTAR }}
                </p>
            </div>

            <!-- Formulir Pencarian, Filter, dan Urutan (pola sama dengan Pemantauan) -->
            <form method="GET" action="{{ route('riwayat-tinjauan.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="md:col-span-4">
                    <label for="cari" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_CARI }}</label>
                    <div class="relative">
                        <input type="text" id="cari" name="cari" value="{{ request('cari') }}"
                            placeholder="{{ LokerLensCopy::RIWAYAT_TINJAUAN_PLACEHOLDER_CARI }}"
                            class="w-full pl-3 pr-10 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none" />
                        @if(request('cari'))
                            <a href="{{ route('riwayat-tinjauan.index') }}" class="absolute right-3 top-2.5 text-xs text-gray-400 hover:text-gray-600">✕</a>
                        @endif
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label for="filter_hasil" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL }}</label>
                    <select id="filter_hasil" name="filter_hasil" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">{{ LokerLensCopy::RIWAYAT_TINJAUAN_SEMUA_HASIL }}</option>
                        @foreach(HasilTinjauan::cases() as $hasil)
                            <option value="{{ $hasil->value }}" {{ request('filter_hasil') === $hasil->value ? 'selected' : '' }}>{{ $hasil->value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="filter_hasil_akhir" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL_AKHIR }}</label>
                    <select id="filter_hasil_akhir" name="filter_hasil_akhir" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">{{ LokerLensCopy::RIWAYAT_TINJAUAN_SEMUA_HASIL_AKHIR }}</option>
                        @foreach(RiwayatTinjauanController::PILIHAN_HASIL_AKHIR as $pilihan)
                            <option value="{{ $pilihan }}" {{ request('filter_hasil_akhir') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="urutan" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_URUTAN }}</label>
                    <select id="urutan" name="urutan" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        @foreach(RiwayatTinjauanController::PILIHAN_URUTAN as $pilihan)
                            <option value="{{ $pilihan }}" {{ request('urutan') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <button type="submit" class="w-full py-2 px-3 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs transition flex items-center justify-center">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Tabel Kasus yang Sudah Ditinjau -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
                    <thead class="bg-gray-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-3">{{ LokerLensCopy::KOLOM_NOMOR_TIKET }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::KOLOM_PERUSAHAAN }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::KOLOM_POSISI }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::LABEL_SKOR_RISIKO }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::KOLOM_ANALIS }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::KOLOM_TANGGAL_DITINJAU }}</th>
                            <th class="px-4 py-3">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL_AKHIR }}</th>
                            <th class="px-4 py-3 text-right">{{ LokerLensCopy::KOLOM_AKSI }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($daftar as $item)
                            @php
                                $pengaduan = $item->pengaduan;
                                $lowongan = $pengaduan->lowonganKerja ?? null;
                                $tinjauan = $item->tinjauanAnalis;
                                $terkonfirmasi = $tinjauan->hasil_tinjauan === HasilTinjauan::TerkonfirmasiMencurigakan->value;
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition" data-testid="baris-riwayat-tinjauan">
                                <td class="px-4 py-3.5 font-mono font-bold text-teal-800">{{ $pengaduan->nomor_tiket }}</td>
                                <td class="px-4 py-3.5 font-bold text-navy-900">{{ $lowongan->perusahaan->nama_perusahaan ?? '-' }}</td>
                                <td class="px-4 py-3.5 font-medium text-gray-900">{{ $lowongan->posisi_lowongan ?? '-' }}</td>

                                <!-- Skor risiko: warna dari HasilKlasifikasi::warna_risiko -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->warna_risiko['badge'] }}">
                                        {{ number_format($item->confidence_score, 0) }}%
                                    </span>
                                    <div class="text-[10px] text-gray-400 mt-0.5">{{ $item->label_risiko }}</div>
                                </td>

                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $terkonfirmasi ? 'bg-red-100 text-risk-high border border-red-200' : 'bg-gray-100 text-gray-700 border border-gray-200' }}">
                                        {{ $tinjauan->hasil_tinjauan }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-gray-900">{{ $tinjauan->analis->name ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-gray-600 whitespace-nowrap">
                                    {{ $tinjauan->tanggal_tinjauan ? $tinjauan->tanggal_tinjauan->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}
                                </td>

                                <!-- Hasil akhir: pengelompokan status kasus (warna badge dari StatusKasus) -->
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold whitespace-nowrap {{ $pengaduan->warna_badge }}">
                                        {{ RiwayatTinjauanController::hasilAkhir($pengaduan->status_pengaduan) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-right">
                                    <a href="{{ route('riwayat-tinjauan.detail', $item->id_klasifikasi) }}" class="inline-flex items-center px-3 py-1.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs shadow transition whitespace-nowrap">
                                        {{ LokerLensCopy::BTN_LIHAT_DETAIL }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        @if($adaFilter)
                                            {{-- Ada data, tetapi tidak cocok dengan pencarian/filter --}}
                                            <p class="mt-3 text-base font-bold text-navy-900">{{ LokerLensCopy::DATA_TIDAK_DITEMUKAN }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ LokerLensCopy::TINJAUAN_TIDAK_DITEMUKAN_KETERANGAN }}</p>
                                            <a href="{{ route('riwayat-tinjauan.index') }}" class="mt-4 inline-block text-xs font-semibold text-teal-600 hover:underline">
                                                {{ LokerLensCopy::BTN_RESET_FILTER }}
                                            </a>
                                        @else
                                            <p class="mt-3 text-base font-bold text-navy-900">{{ LokerLensCopy::BELUM_ADA_TINJAUAN }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ LokerLensCopy::BELUM_ADA_TINJAUAN_KETERANGAN }}</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-600 gap-3 border-t border-gray-100 pt-4">
                <div>
                    Menampilkan {{ $daftar->firstItem() ?? 0 }} - {{ $daftar->lastItem() ?? 0 }} dari {{ $daftar->total() }}
                </div>
                <div>
                    {{ $daftar->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
