<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Header Halaman (Bagian 6.4.1) -->
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Meninjau Lowongan Kerja dengan Prioritas Tinggi
            </h1>
            <p class="text-sm text-gray-300 mt-1">
                Daftar antrean verifikasi laporan indikasi penipuan lowongan kerja yang membutuhkan tinjauan manual prioritas.
            </p>
        </div>

        <!-- Banner Flash Sukses -->
        @if(session('success'))
            <div class="bg-teal-800 border-l-4 border-teal-400 p-4 rounded-r-lg text-white text-sm shadow">
                {{ session('success') }}
            </div>
        @endif

        <!-- Kartu Utama: Daftar Lowongan Prioritas Tinggi -->
        <div class="bg-white rounded-card shadow-xl p-6 sm:p-8 text-navy-900 border border-gray-100">
            <!-- Header Kartu & Jumlah Antrean -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-gray-100 gap-2">
                <div>
                    <!-- Teks Wajib BDD: "Daftar Lowongan Prioritas Tinggi" -->
                    <h2 class="text-xl font-bold tracking-tight text-navy-900">
                        Daftar Lowongan Prioritas Tinggi
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Laporan otomatis dengan confidence score tinggi yang dialokasikan ke Analis.
                    </p>
                </div>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-risk-high border border-red-200">
                    <span class="w-2 h-2 rounded-full bg-risk-high mr-2 animate-ping"></span>
                    {{ $hasilKlasifikasi->total() }} Menunggu Verifikasi Manual
                </div>
            </div>

            <!-- Formulir Filter & Pencarian (BDD Hooks: nomor_tiket, filter_skor, urutan, Cari) -->
            <form method="GET" action="{{ route('tinjau-lowongan.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                <!-- Input Pencarian -->
                <div class="md:col-span-5">
                    <label for="nomor_tiket" class="block text-xs font-semibold text-gray-700 mb-1">Pencarian Laporan</label>
                    <div class="relative">
                        <input type="text" id="nomor_tiket" name="nomor_tiket" value="{{ request('nomor_tiket') }}"
                            placeholder="Cari ID / Perusahaan / Kata kunci"
                            class="w-full pl-3 pr-10 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none" />
                        @if(request('nomor_tiket'))
                            <a href="{{ route('tinjau-lowongan.index') }}" class="absolute right-3 top-2.5 text-xs text-gray-400 hover:text-gray-600">✕</a>
                        @endif
                    </div>
                </div>

                <!-- Select Filter Skor -->
                <div class="md:col-span-3">
                    <label for="filter_skor" class="block text-xs font-semibold text-gray-700 mb-1">Filter Skor</label>
                    <select id="filter_skor" name="filter_skor" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">Semua Skor</option>
                        <option value="Skor Tinggi" {{ request('filter_skor') === 'Skor Tinggi' ? 'selected' : '' }}>Skor Tinggi (≥ 75%)</option>
                        <option value="Skor Sedang" {{ request('filter_skor') === 'Skor Sedang' ? 'selected' : '' }}>Skor Sedang (50% - 74%)</option>
                    </select>
                </div>

                <!-- Select Urutan -->
                <div class="md:col-span-3">
                    <label for="urutan" class="block text-xs font-semibold text-gray-700 mb-1">Urutkan</label>
                    <select id="urutan" name="urutan" class="w-full px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="Skor Tertinggi" {{ request('urutan') === 'Skor Tertinggi' ? 'selected' : '' }}>Skor Tertinggi</option>
                        <option value="Skor Terendah" {{ request('urutan') === 'Skor Terendah' ? 'selected' : '' }}>Skor Terendah</option>
                        <option value="Terbaru" {{ request('urutan') === 'Terbaru' ? 'selected' : '' }}>Terbaru</option>
                    </select>
                </div>

                <!-- Tombol Cari -->
                <div class="md:col-span-1 flex items-end">
                    <button type="submit" class="w-full py-2 px-3 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs transition flex items-center justify-center">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Label Kriteria Filter / Hasil Pencarian (BDD Hooks) -->
            @if(request('nomor_tiket') && $hasilKlasifikasi->count() > 0)
                <div class="mb-4 text-xs font-bold text-teal-700 flex items-center gap-1.5 bg-teal-50 px-3 py-2 rounded-lg border border-teal-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>Hasil Pencarian: "{{ request('nomor_tiket') }}"</span>
                </div>
            @elseif($adaFilter && $hasilKlasifikasi->count() > 0)
                <div class="mb-4 text-xs font-bold text-teal-700 flex items-center gap-1.5 bg-teal-50 px-3 py-2 rounded-lg border border-teal-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    <span>Daftar Sesuai Kriteria Filter</span>
                </div>
            @endif

            <!-- Tabel Daftar Lowongan Prioritas Tinggi (Bagian 6.4.1) -->
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
                    <thead class="bg-gray-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-4 py-3">ID LOWONGAN</th>
                            <th class="px-4 py-3">NAMA PERUSAHAAN</th>
                            <th class="px-4 py-3">POSISI DITAWARKAN</th>
                            <th class="px-4 py-3">CONFIDENCE SCORE</th>
                            <th class="px-4 py-3">STATUS SISTEM</th>
                            <th class="px-4 py-3 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($hasilKlasifikasi as $item)
                            @php
                                $pengaduan = $item->pengaduan;
                                $lowongan = $pengaduan->lowonganKerja ?? null;
                                $perusahaan = $lowongan->perusahaan ?? null;
                                $sudahDitinjau = $item->tinjauanAnalis !== null;
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition">
                                <!-- ID Lowongan / Tiket -->
                                <td class="px-4 py-3.5 font-mono font-bold text-teal-800">
                                    {{ $pengaduan->nomor_tiket }}
                                </td>

                                <!-- Nama Perusahaan -->
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-navy-900">
                                        {{ $perusahaan->nama_perusahaan ?? 'Entitas Tidak Terdaftar' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        <span>{{ $perusahaan->status_terdaftar ?? 'Entitas Tak Terdaftar Kemenaker / AHU' }}</span>
                                    </div>
                                </td>

                                <!-- Posisi Ditawarkan -->
                                <td class="px-4 py-3.5 font-medium text-gray-900">
                                    {{ $lowongan->posisi_lowongan ?? '-' }}
                                </td>

                                <!-- Confidence Score -->
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $item->confidence_score >= 75 ? 'bg-red-100 text-risk-high border border-red-200' : 'bg-amber-100 text-risk-mid border border-amber-200' }}">
                                        {{ number_format($item->confidence_score, 0) }}% Indikasi Penipuan
                                    </span>
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        {{ $item->confidence_score >= 75 ? 'Skor Kritis (Tingkat Bahaya)' : 'Perlu Tinjauan' }}
                                    </div>
                                </td>

                                <!-- Status Sistem -->
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $pengaduan->warna_badge ?? 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $pengaduan->status_pengaduan }}
                                    </span>
                                </td>

                                <!-- Aksi (BDD Hook: "Lihat Detail") -->
                                <td class="px-4 py-3.5 text-right">
                                    @if($pengaduan->status_pengaduan === \App\Enums\StatusKasus::SedangDitinjau)
                                        <a href="{{ route('tinjau-lowongan.detail', $item->id_klasifikasi) }}" class="inline-flex items-center px-3 py-1.5 rounded-control bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs shadow transition">
                                            Sedang Ditinjau
                                        </a>
                                    @else
                                        <a href="{{ route('tinjau-lowongan.detail', $item->id_klasifikasi) }}" class="inline-flex items-center px-3 py-1.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs shadow transition">
                                            Lihat Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <!-- Pesan BDD Jika Tidak Ditemukan: "Data Lowongan Tidak Ditemukan" -->
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="max-w-xs mx-auto">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="mt-3 text-base font-bold text-navy-900">
                                            Data Lowongan Tidak Ditemukan
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Tidak ada lowongan prioritas tinggi yang cocok dengan kata kunci atau filter yang Anda pilih.
                                        </p>
                                        <a href="{{ route('tinjau-lowongan.index') }}" class="mt-4 inline-block text-xs font-semibold text-teal-600 hover:underline">
                                            Reset Filter Pencarian
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi (Bagian 6.4.1) -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-600 gap-3 border-t border-gray-100 pt-4">
                <div>
                    Menampilkan {{ $hasilKlasifikasi->firstItem() ?? 0 }} - {{ $hasilKlasifikasi->lastItem() ?? 0 }} dari {{ $hasilKlasifikasi->total() }} Lowongan Prioritas
                </div>
                <div>
                    {{ $hasilKlasifikasi->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
