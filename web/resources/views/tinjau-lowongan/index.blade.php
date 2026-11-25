@use('App\Constants\LokerLensCopy')
@use('App\Enums\StatusKasus')

<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Header Halaman (Bagian 6.4.1) -->
        <x-judul-halaman judul="Meninjau Lowongan Kerja dengan Prioritas Tinggi"
            deskripsi="Daftar antrean verifikasi laporan indikasi penipuan lowongan kerja yang membutuhkan tinjauan manual prioritas." />

        <!-- Kartu Utama: Daftar Lowongan Prioritas Tinggi -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
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
                <x-badge :nada="\App\Support\WarnaBadge::MERAH">
                    <span class="w-2 h-2 rounded-full bg-risk-high mr-2 animate-ping" aria-hidden="true"></span>
                    {{ $hasilKlasifikasi->total() }} Menunggu Verifikasi Manual
                </x-badge>
            </div>

            <!-- Formulir Filter & Pencarian (BDD Hooks: nomor_tiket, filter_skor, urutan, Cari).
                 filterOtomatis (resources/js/app.js): dropdown langsung terkirim, pencarian setelah berhenti mengetik. -->
            <x-filter :action="route('tinjau-lowongan.index')" class="mb-6">
                <x-filter.cari name="nomor_tiket" label="Pencarian Laporan" placeholder="Cari ID / Perusahaan / Kata kunci" :hapus="route('tinjau-lowongan.index')" />

                <x-filter.pilih name="filter_skor" label="Filter Skor">
                    <option value="">Semua Skor</option>
                    {{-- Batas mengikuti ambang prioritas (config/lokerlens.php) --}}
                    @php $ambang = (int) config('lokerlens.ambang_prioritas'); @endphp
                    <option value="Skor Tinggi" {{ request('filter_skor') === 'Skor Tinggi' ? 'selected' : '' }}>Skor Tinggi (≥ {{ $ambang }}%)</option>
                    <option value="Skor Sedang" {{ request('filter_skor') === 'Skor Sedang' ? 'selected' : '' }}>Skor Sedang (50% - {{ $ambang - 1 }}%)</option>
                </x-filter.pilih>

                <x-filter.pilih name="urutan" label="Urutkan">
                    <option value="Skor Tertinggi" {{ request('urutan') === 'Skor Tertinggi' ? 'selected' : '' }}>Skor Tertinggi</option>
                    <option value="Skor Terendah" {{ request('urutan') === 'Skor Terendah' ? 'selected' : '' }}>Skor Terendah</option>
                    <option value="Terbaru" {{ request('urutan') === 'Terbaru' ? 'selected' : '' }}>Terbaru</option>
                </x-filter.pilih>
            </x-filter>

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
            <x-tabel>
                <x-slot:kepala>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_TIKET }}</th>
                    <th class="min-w-[12rem]">NAMA PERUSAHAAN</th>
                    <th class="min-w-[10rem]">POSISI DITAWARKAN</th>
                    <th class="whitespace-nowrap">CONFIDENCE SCORE</th>
                    <th class="whitespace-nowrap">STATUS SISTEM</th>
                    <th class="text-right whitespace-nowrap">AKSI</th>
                </x-slot:kepala>
                        @forelse($hasilKlasifikasi as $item)
                            @php
                                $pengaduan = $item->pengaduan;
                                $lowongan = $pengaduan->lowonganKerja ?? null;
                                $perusahaan = $lowongan->perusahaan ?? null;
                                $sudahDitinjau = $item->tinjauanAnalis !== null;
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <!-- ID Lowongan / Tiket -->
                                <td class="font-mono font-bold text-teal-800 whitespace-nowrap">
                                    {{ $pengaduan->nomor_tiket }}
                                </td>

                                <!-- Nama Perusahaan -->
                                <td>
                                    <div class="font-bold text-navy-900">
                                        {{ $perusahaan->nama_perusahaan ?? '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        <span>{{ $perusahaan->status_terdaftar ?? '-' }}</span>
                                    </div>
                                </td>

                                <!-- Posisi Ditawarkan -->
                                <td class="font-medium text-gray-900">
                                    {{ $lowongan->posisi_lowongan ?? '-' }}
                                </td>

                                <!-- Confidence Score: warna & keterangan sesuai tingkat risiko -->
                                <td>
                                    <x-badge-skor :klasifikasi="$item" />
                                </td>

                                <!-- Status Sistem -->
                                <td>
                                    <x-badge-status :status="$pengaduan->status_pengaduan" />
                                </td>

                                <!-- Aksi: teks tombol beda dari teks status. Atribut title "Lihat Detail ..."
                                     dipakai langkah BDD 'I follow "Lihat Detail"' (Mink juga mencocokkan title). -->
                                <td class="text-right whitespace-nowrap">
                                    <x-tombol ukuran="sm" :href="route('tinjau-lowongan.detail', $item->id_klasifikasi)"
                                        title="{{ LokerLensCopy::BTN_LIHAT_DETAIL }} {{ $pengaduan->nomor_tiket }}">
                                        {{ $pengaduan->status_pengaduan === StatusKasus::SedangDitinjau ? LokerLensCopy::BTN_LANJUTKAN_TINJAUAN : LokerLensCopy::BTN_MULAI_TINJAU }}
                                    </x-tombol>
                                </td>
                            </tr>
                        @empty
                            <!-- Pesan BDD Jika Tidak Ditemukan: "Data Lowongan Tidak Ditemukan" -->
                            <tr>
                                <td colspan="6">
                                    <x-pesan-kosong :kartu="false" judul="Data Lowongan Tidak Ditemukan"
                                        keterangan="Tidak ada lowongan prioritas tinggi yang cocok dengan kata kunci atau filter yang Anda pilih.">
                                        <x-tombol varian="sekunder" :href="route('tinjau-lowongan.index')">Reset Filter Pencarian</x-tombol>
                                    </x-pesan-kosong>
                                </td>
                            </tr>
                        @endforelse
            </x-tabel>

            <!-- Paginasi (Bagian 6.4.1) -->
            <x-paginasi :daftar="$hasilKlasifikasi" satuan="Lowongan Prioritas" />
        </div>
    </div>
</x-app-layout>
