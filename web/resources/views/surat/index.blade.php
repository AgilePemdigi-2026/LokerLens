@use('App\Constants\LokerLensCopy')
@use('App\Http\Controllers\SuratController')
@use('App\Support\WarnaBadge')

{{-- Daftar Surat Kepala Bidang (issue #8). Gaya mengikuti Riwayat Tinjauan dan Arsip Kasus. --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Header Halaman -->
        <x-judul-halaman label="DOKUMEN RESMI" :judul="LokerLensCopy::SURAT_JUDUL" :deskripsi="LokerLensCopy::SURAT_DESKRIPSI" />

        <!-- Kotak Angka: dari semua surat (tanpa filter) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu-angka id="stat-total-surat" :label="LokerLensCopy::SURAT_STAT_TOTAL" :angka="$statistik['total']" />
            <x-kartu-angka id="stat-draf" :label="LokerLensCopy::SURAT_STAT_DRAF" :angka="$statistik['draf']" :nada="WarnaBadge::surat(false)" />
            <x-kartu-angka id="stat-terbit" :label="LokerLensCopy::SURAT_STAT_TERBIT" :angka="$statistik['terbit']" :nada="WarnaBadge::surat(true)" />
        </div>

        <!-- Kartu Utama: Daftar Surat -->
        <div class="bg-white rounded-card shadow-lg p-6 sm:p-8 text-navy-900 border border-gray-100">
            <div class="pb-4 mb-6 border-b border-gray-100">
                <h2 class="text-xl font-bold tracking-tight text-navy-900">
                    {{ LokerLensCopy::SURAT_JUDUL_DAFTAR }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ LokerLensCopy::SURAT_KETERANGAN_DAFTAR }}
                </p>
            </div>

            <!-- Formulir Pencarian, Filter, dan Urutan (filter otomatis, tombol Cari tetap ada) -->
            <x-filter :action="route('surat.index')" class="mb-6">
                <x-filter.cari :label="LokerLensCopy::SURAT_LABEL_CARI" :placeholder="LokerLensCopy::SURAT_PLACEHOLDER_CARI" :hapus="route('surat.index')" />

                <x-filter.pilih name="jenis_dokumen" :label="LokerLensCopy::SURAT_LABEL_JENIS">
                    <option value="">{{ LokerLensCopy::SURAT_SEMUA_JENIS }}</option>
                    @foreach(SuratController::PILIHAN_JENIS as $jenis)
                        <option value="{{ $jenis }}" {{ request('jenis_dokumen') === $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                    @endforeach
                </x-filter.pilih>

                <x-filter.pilih name="status" :label="LokerLensCopy::SURAT_LABEL_STATUS">
                    <option value="">{{ LokerLensCopy::SURAT_SEMUA_STATUS }}</option>
                    @foreach(SuratController::PILIHAN_STATUS as $nilai => $label)
                        <option value="{{ $nilai }}" {{ request('status') === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-filter.pilih>

                <x-filter.pilih name="urutan" :label="LokerLensCopy::SURAT_LABEL_URUTAN">
                    @foreach(SuratController::PILIHAN_URUTAN as $pilihan)
                        <option value="{{ $pilihan }}" {{ request('urutan') === $pilihan ? 'selected' : '' }}>{{ $pilihan }}</option>
                    @endforeach
                </x-filter.pilih>
            </x-filter>

            <!-- Tabel Surat -->
            <x-tabel :rapat="true">
                <x-slot:kepala>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_SURAT }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_NOMOR_TIKET }}</th>
                    <th class="min-w-[10rem]">{{ LokerLensCopy::KOLOM_PERUSAHAAN }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_JENIS_DOKUMEN }}</th>
                    <th class="min-w-[11rem]">{{ LokerLensCopy::KOLOM_TUJUAN }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_STATUS }}</th>
                    <th class="whitespace-nowrap">{{ LokerLensCopy::KOLOM_TANGGAL }}</th>
                    <th class="text-right whitespace-nowrap">{{ LokerLensCopy::KOLOM_AKSI }}</th>
                </x-slot:kepala>
                        @forelse($surat as $item)
                            @php
                                $pengaduan = $item->tindakLanjutKasus?->pengaduan;
                                $terbit = $item->status_penerbitan === 'Terbit';
                                // Surat terbit memakai tanggal terbit, draf memakai waktu terakhir disimpan
                                $tanggal = $terbit ? $item->tanggal_terbit : $item->updated_at;
                            @endphp
                            <tr class="hover:bg-gray-50 transition" data-testid="baris-surat">
                                <td class="font-mono font-bold whitespace-nowrap {{ $terbit ? 'text-navy-900' : 'text-gray-400' }}">
                                    {{ $terbit ? $item->nomor_surat : LokerLensCopy::SURAT_STATUS_DRAF }}
                                </td>
                                <td class="font-mono font-bold text-teal-800 whitespace-nowrap">{{ $pengaduan->nomor_tiket ?? '-' }}</td>
                                <td class="font-bold text-navy-900">{{ $pengaduan?->lowonganKerja?->perusahaan?->nama_perusahaan ?? '-' }}</td>
                                <td>
                                    <x-badge>{{ $item->jenis_dokumen }}</x-badge>
                                </td>
                                <td class="text-gray-700">{{ $item->tujuan_instansi ?: '-' }}</td>

                                <!-- Status: badge berbeda untuk Draf dan Terbit -->
                                <td>
                                    <x-badge :nada="WarnaBadge::surat($terbit)" :titik="true">{{ $terbit ? LokerLensCopy::SURAT_STATUS_TERBIT : LokerLensCopy::SURAT_STATUS_DRAF }}</x-badge>
                                </td>

                                <!-- Tanggal di baris pertama; jam dan keterangan di baris kedua supaya kolom tidak terlalu lebar -->
                                <td class="text-gray-600 whitespace-nowrap">
                                    {{ $tanggal ? $tanggal->translatedFormat('d M Y') : '-' }}
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        @if($tanggal){{ $tanggal->format('H:i') }} WIB · @endif{{ $terbit ? LokerLensCopy::SURAT_KET_TANGGAL_TERBIT : LokerLensCopy::SURAT_KET_TANGGAL_DRAF }}
                                    </div>
                                </td>

                                <!-- Aksi: draf dilanjutkan di Buat Surat, surat terbit dibuka di halaman surat terbit -->
                                <td class="text-right whitespace-nowrap">
                                    @if($pengaduan)
                                        @if($terbit)
                                            <x-tombol ukuran="sm" :href="route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $item->id_surat])">{{ LokerLensCopy::BTN_LIHAT_SURAT }}</x-tombol>
                                        @else
                                            <x-tombol ukuran="sm" varian="aksi" :href="route('buat-surat.index', $pengaduan->id_pengaduan)">{{ LokerLensCopy::BTN_LANJUTKAN_DRAF }}</x-tombol>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    @if($adaFilter)
                                        {{-- Ada surat, tetapi tidak cocok dengan pencarian/filter --}}
                                        <x-pesan-kosong :kartu="false" :judul="LokerLensCopy::SURAT_TIDAK_DITEMUKAN" :keterangan="LokerLensCopy::SURAT_TIDAK_DITEMUKAN_KETERANGAN">
                                            <x-tombol varian="sekunder" :href="route('surat.index')">{{ LokerLensCopy::BTN_RESET_FILTER }}</x-tombol>
                                        </x-pesan-kosong>
                                    @else
                                        <x-pesan-kosong :kartu="false" :judul="LokerLensCopy::BELUM_ADA_SURAT" :keterangan="LokerLensCopy::BELUM_ADA_SURAT_KETERANGAN" />
                                    @endif
                                </td>
                            </tr>
                        @endforelse
            </x-tabel>

            <!-- Paginasi -->
            <x-paginasi :daftar="$surat" />
        </div>
    </div>
</x-app-layout>
