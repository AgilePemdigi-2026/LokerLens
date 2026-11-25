@use('App\Constants\LokerLensCopy')
@use('App\Enums\HasilTinjauan')
@use('App\Http\Controllers\RiwayatTinjauanController')
@use('App\Support\WarnaBadge')

{{-- Riwayat Tinjauan Analis (issue #25). Gaya mengikuti halaman Tinjau Lowongan (tinjau-lowongan/index). --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Header Halaman -->
        <x-judul-halaman :judul="LokerLensCopy::RIWAYAT_TINJAUAN_JUDUL" :deskripsi="LokerLensCopy::RIWAYAT_TINJAUAN_DESKRIPSI" />

        <!-- Kotak Angka: dari semua kasus yang sudah ditinjau (tanpa filter) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu-angka id="stat-total-ditinjau" :label="LokerLensCopy::RIWAYAT_TINJAUAN_STAT_TOTAL" :angka="$statistik['total']" />
            <x-kartu-angka id="stat-terkonfirmasi" :label="HasilTinjauan::TerkonfirmasiMencurigakan->value" :angka="$statistik['terkonfirmasi']"
                :nada="WarnaBadge::hasilTinjauan(HasilTinjauan::TerkonfirmasiMencurigakan)" />
            <x-kartu-angka id="stat-tidak-terbukti" :label="HasilTinjauan::TidakTerbukti->value" :angka="$statistik['tidakTerbukti']"
                :nada="WarnaBadge::hasilTinjauan(HasilTinjauan::TidakTerbukti)" />
        </div>

        <!-- Kartu Utama: Daftar Lowongan yang Sudah Ditinjau -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight text-navy-900">
                    {{ LokerLensCopy::RIWAYAT_TINJAUAN_JUDUL_DAFTAR }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ LokerLensCopy::RIWAYAT_TINJAUAN_KETERANGAN_DAFTAR }}
                </p>
            </div>

            <!-- Formulir Pencarian, Filter, dan Urutan (pola sama dengan Tinjau Lowongan, filter otomatis) -->
            <x-filter :action="route('riwayat-tinjauan.index')" class="mb-6">
                <x-filter.cari :label="LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_CARI" :placeholder="LokerLensCopy::RIWAYAT_TINJAUAN_PLACEHOLDER_CARI" :hapus="route('riwayat-tinjauan.index')" />

                <x-filter.pilih name="filter_hasil" :label="LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL">
                    <option value="">{{ LokerLensCopy::RIWAYAT_TINJAUAN_SEMUA_HASIL }}</option>
                    @foreach(HasilTinjauan::cases() as $hasil)
                        <option value="{{ $hasil->value }}" {{ request('filter_hasil') === $hasil->value ? 'selected' : '' }}>{{ $hasil->value }}</option>
                    @endforeach
                </x-filter.pilih>

                <x-filter.pilih name="filter_hasil_akhir" :label="LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL_AKHIR">
                    <option value="">{{ LokerLensCopy::RIWAYAT_TINJAUAN_SEMUA_HASIL_AKHIR }}</option>
                    @foreach(RiwayatTinjauanController::PILIHAN_HASIL_AKHIR as $pilihan)
                        <option value="{{ $pilihan }}" {{ request('filter_hasil_akhir') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                    @endforeach
                </x-filter.pilih>

                <x-filter.pilih name="urutan" :label="LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_URUTAN">
                    @foreach(RiwayatTinjauanController::PILIHAN_URUTAN as $pilihan)
                        <option value="{{ $pilihan }}" {{ request('urutan') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                    @endforeach
                </x-filter.pilih>
            </x-filter>

            <!-- Tabel Kasus yang Sudah Ditinjau -->
            <x-tabel :rapat="true">
                <x-slot:kepala>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_TIKET }}</th>
                    <th class="min-w-[10rem]">{{ LokerLensCopy::KOLOM_PERUSAHAAN }}</th>
                    <th class="min-w-[9rem]">{{ LokerLensCopy::KOLOM_POSISI }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::LABEL_SKOR_RISIKO }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL }}</th>
                    <th>{{ LokerLensCopy::KOLOM_ANALIS }}</th>
                    <th class="min-w-[7rem]">{{ LokerLensCopy::KOLOM_TANGGAL_DITINJAU }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::RIWAYAT_TINJAUAN_LABEL_HASIL_AKHIR }}</th>
                    <th class="text-right whitespace-nowrap">{{ LokerLensCopy::KOLOM_AKSI }}</th>
                </x-slot:kepala>
                        @forelse($daftar as $item)
                            @php
                                $pengaduan = $item->pengaduan;
                                $lowongan = $pengaduan->lowonganKerja ?? null;
                                $tinjauan = $item->tinjauanAnalis;
                            @endphp
                            <tr class="hover:bg-gray-50 transition" data-testid="baris-riwayat-tinjauan">
                                <td class="font-mono font-bold text-teal-800 whitespace-nowrap">{{ $pengaduan->nomor_tiket }}</td>
                                <td class="font-bold text-navy-900">{{ $lowongan->perusahaan->nama_perusahaan ?? '-' }}</td>
                                <td class="font-medium text-gray-900">{{ $lowongan->posisi_lowongan ?? '-' }}</td>

                                <!-- Skor risiko: warna dari HasilKlasifikasi::warna_risiko -->
                                <td>
                                    <x-badge-skor :klasifikasi="$item" />
                                </td>

                                <td>
                                    <x-badge :nada="WarnaBadge::hasilTinjauan($tinjauan->hasil_tinjauan)" class="!whitespace-normal">{{ $tinjauan->hasil_tinjauan }}</x-badge>
                                </td>
                                <td class="text-gray-900">{{ $tinjauan->analis->name ?? '-' }}</td>
                                <td class="text-gray-600">
                                    {{ $tinjauan->tanggal_tinjauan ? $tinjauan->tanggal_tinjauan->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}
                                </td>

                                <!-- Hasil akhir: pengelompokan status kasus (teks = kelompok, warna = status kasus) -->
                                <td>
                                    <x-badge-status :status="$pengaduan->status_pengaduan">{{ RiwayatTinjauanController::hasilAkhir($pengaduan->status_pengaduan) }}</x-badge-status>
                                </td>

                                <td class="text-right">
                                    <x-tombol ukuran="sm" :href="route('riwayat-tinjauan.detail', $item->id_klasifikasi)">{{ LokerLensCopy::BTN_LIHAT_DETAIL }}</x-tombol>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    @if($adaFilter)
                                        {{-- Ada data, tetapi tidak cocok dengan pencarian/filter --}}
                                        <x-pesan-kosong :kartu="false" :judul="LokerLensCopy::DATA_TIDAK_DITEMUKAN" :keterangan="LokerLensCopy::TINJAUAN_TIDAK_DITEMUKAN_KETERANGAN">
                                            <x-tombol varian="sekunder" :href="route('riwayat-tinjauan.index')">{{ LokerLensCopy::BTN_RESET_FILTER }}</x-tombol>
                                        </x-pesan-kosong>
                                    @else
                                        <x-pesan-kosong :kartu="false" :judul="LokerLensCopy::BELUM_ADA_TINJAUAN" :keterangan="LokerLensCopy::BELUM_ADA_TINJAUAN_KETERANGAN" />
                                    @endif
                                </td>
                            </tr>
                        @endforelse
            </x-tabel>

            <!-- Paginasi -->
            <x-paginasi :daftar="$daftar" />
        </div>
    </div>
</x-app-layout>
