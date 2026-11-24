@use('App\Constants\LokerLensCopy')
@use('App\Http\Controllers\SuratController')

{{-- Daftar Surat Kepala Bidang (issue #8). Gaya mengikuti Riwayat Tinjauan dan Arsip Kasus. --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
                <!-- Header Halaman -->
        <div class="relative overflow-hidden bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl">
            <div class="relative z-10">
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2">
                    DOKUMEN RESMI
                </p>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    {{ LokerLensCopy::SURAT_JUDUL }}
                </h1>
                <p class="text-sm text-white mt-2 max-w-2xl">
                    {{ LokerLensCopy::SURAT_DESKRIPSI }}
                </p>
            </div>

            <div class="absolute -right-8 -bottom-12 w-40 h-40 rounded-full border-[20px] border-teal-400/10"></div>
            <div class="absolute -right-2 -bottom-6 w-24 h-24 rounded-full border-[12px] border-teal-400/10"></div>
        </div>

        <!-- Kotak Angka: dari semua surat (tanpa filter) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ LokerLensCopy::SURAT_STAT_TOTAL }}</p>
                <p id="stat-total-surat" class="text-3xl font-extrabold text-navy-900 mt-1">{{ $statistik['total'] }}</p>
            </div>
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ LokerLensCopy::SURAT_STAT_DRAF }}</p>
                <p id="stat-draf" class="text-3xl font-extrabold text-risk-mid mt-1">{{ $statistik['draf'] }}</p>
            </div>
            <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ LokerLensCopy::SURAT_STAT_TERBIT }}</p>
                <p id="stat-terbit" class="text-3xl font-extrabold text-risk-low mt-1">{{ $statistik['terbit'] }}</p>
            </div>
        </div>

        <!-- Kartu Utama: Daftar Surat -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight text-navy-900">
                    {{ LokerLensCopy::SURAT_JUDUL_DAFTAR }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ LokerLensCopy::SURAT_KETERANGAN_DAFTAR }}
                </p>
            </div>

            <!-- Formulir Pencarian, Filter, dan Urutan (filter otomatis, tombol Cari tetap ada) -->
            <form method="GET" action="{{ route('surat.index') }}" x-data="filterOtomatis" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="md:col-span-4">
                    <label for="cari" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::SURAT_LABEL_CARI }}</label>
                    <div class="relative">
                        <input type="text" id="cari" name="cari" value="{{ request('cari') }}"
                            placeholder="{{ LokerLensCopy::SURAT_PLACEHOLDER_CARI }}"
                            class="w-full pl-3 pr-10 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none" />
                        @if(request('cari'))
                            <a href="{{ route('surat.index') }}" class="absolute right-3 top-2.5 text-xs text-gray-400 hover:text-gray-600">✕</a>
                        @endif
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label for="jenis_dokumen" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::SURAT_LABEL_JENIS }}</label>
                    <select id="jenis_dokumen" name="jenis_dokumen" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">{{ LokerLensCopy::SURAT_SEMUA_JENIS }}</option>
                        @foreach(SuratController::PILIHAN_JENIS as $jenis)
                            <option value="{{ $jenis }}" {{ request('jenis_dokumen') === $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="status" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::SURAT_LABEL_STATUS }}</label>
                    <select id="status" name="status" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">{{ LokerLensCopy::SURAT_SEMUA_STATUS }}</option>
                        @foreach(SuratController::PILIHAN_STATUS as $nilai => $label)
                            <option value="{{ $nilai }}" {{ request('status') === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="urutan" class="block text-xs font-semibold text-gray-700 mb-1">{{ LokerLensCopy::SURAT_LABEL_URUTAN }}</label>
                    <select id="urutan" name="urutan" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        @foreach(SuratController::PILIHAN_URUTAN as $pilihan)
                            <option value="{{ $pilihan }}" {{ request('urutan') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-1 flex items-end">
                    <button type="submit" class="w-full py-2 px-3 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs transition flex items-center justify-center">
                        {{ LokerLensCopy::BTN_CARI }}
                    </button>
                </div>
            </form>

            <!-- Tabel Surat -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
                    <thead class="bg-gray-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-3 py-3 whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_SURAT }}</th>
                            <th class="px-3 py-3 whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_TIKET }}</th>
                            <th class="px-3 py-3 min-w-[10rem]">{{ LokerLensCopy::KOLOM_PERUSAHAAN }}</th>
                            <th class="px-3 py-3 whitespace-nowrap">{{ LokerLensCopy::KOLOM_JENIS_DOKUMEN }}</th>
                            <th class="px-3 py-3 min-w-[11rem]">{{ LokerLensCopy::KOLOM_TUJUAN }}</th>
                            <th class="px-3 py-3 whitespace-nowrap">{{ LokerLensCopy::KOLOM_STATUS }}</th>
                            <th class="px-3 py-3 whitespace-nowrap">{{ LokerLensCopy::KOLOM_TANGGAL }}</th>
                            <th class="px-3 py-3 text-right whitespace-nowrap">{{ LokerLensCopy::KOLOM_AKSI }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($surat as $item)
                            @php
                                $pengaduan = $item->tindakLanjutKasus?->pengaduan;
                                $terbit = $item->status_penerbitan === 'Terbit';
                                // Surat terbit memakai tanggal terbit, draf memakai waktu terakhir disimpan
                                $tanggal = $terbit ? $item->tanggal_terbit : $item->updated_at;
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition" data-testid="baris-surat">
                                <td class="px-3 py-3.5 font-mono font-bold whitespace-nowrap {{ $terbit ? 'text-navy-900' : 'text-gray-400' }}">
                                    {{ $terbit ? $item->nomor_surat : LokerLensCopy::SURAT_STATUS_DRAF }}
                                </td>
                                <td class="px-3 py-3.5 font-mono font-bold text-teal-800 whitespace-nowrap">{{ $pengaduan->nomor_tiket ?? '-' }}</td>
                                <td class="px-3 py-3.5 font-bold text-navy-900">{{ $pengaduan?->lowonganKerja?->perusahaan?->nama_perusahaan ?? '-' }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $item->jenis_dokumen }}
                                    </span>
                                </td>
                                <td class="px-3 py-3.5 text-gray-700">{{ $item->tujuan_instansi ?: '-' }}</td>

                                <!-- Status: badge berbeda untuk Draf dan Terbit -->
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-xs font-bold border {{ $terbit ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300' }}">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $terbit ? 'bg-emerald-600' : 'bg-amber-500' }}" aria-hidden="true"></span>
                                        {{ $terbit ? LokerLensCopy::SURAT_STATUS_TERBIT : LokerLensCopy::SURAT_STATUS_DRAF }}
                                    </span>
                                </td>

                                <!-- Tanggal di baris pertama; jam dan keterangan di baris kedua supaya kolom tidak terlalu lebar -->
                                <td class="px-3 py-3.5 text-gray-600 whitespace-nowrap">
                                    {{ $tanggal ? $tanggal->translatedFormat('d M Y') : '-' }}
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        @if($tanggal){{ $tanggal->format('H:i') }} WIB · @endif{{ $terbit ? LokerLensCopy::SURAT_KET_TANGGAL_TERBIT : LokerLensCopy::SURAT_KET_TANGGAL_DRAF }}
                                    </div>
                                </td>

                                <!-- Aksi: draf dilanjutkan di Buat Surat, surat terbit dibuka di halaman surat terbit -->
                                <td class="px-3 py-3.5 text-right whitespace-nowrap">
                                    @if($pengaduan)
                                        @if($terbit)
                                            <a href="{{ route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $item->id_surat]) }}"
                                               class="inline-flex items-center whitespace-nowrap px-3 py-1.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs shadow transition">
                                                {{ LokerLensCopy::BTN_LIHAT_SURAT }}
                                            </a>
                                        @else
                                            <a href="{{ route('buat-surat.index', $pengaduan->id_pengaduan) }}"
                                               class="inline-flex items-center whitespace-nowrap px-3 py-1.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow transition">
                                                {{ LokerLensCopy::BTN_LANJUTKAN_DRAF }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        @if($adaFilter)
                                            {{-- Ada surat, tetapi tidak cocok dengan pencarian/filter --}}
                                            <p class="mt-3 text-base font-bold text-navy-900">{{ LokerLensCopy::SURAT_TIDAK_DITEMUKAN }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ LokerLensCopy::SURAT_TIDAK_DITEMUKAN_KETERANGAN }}</p>
                                            <a href="{{ route('surat.index') }}" class="mt-4 inline-block text-xs font-semibold text-teal-600 hover:underline">
                                                {{ LokerLensCopy::BTN_RESET_FILTER }}
                                            </a>
                                        @else
                                            <p class="mt-3 text-base font-bold text-navy-900">{{ LokerLensCopy::BELUM_ADA_SURAT }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ LokerLensCopy::BELUM_ADA_SURAT_KETERANGAN }}</p>
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
                    Menampilkan {{ $surat->firstItem() ?? 0 }} - {{ $surat->lastItem() ?? 0 }} dari {{ $surat->total() }}
                </div>
                <div>
                    {{ $surat->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
