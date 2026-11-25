@use('App\Enums\StatusKasus')
@use('App\Support\WarnaBadge')

<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             1. HERO BANNER — Repositori Arsip Kasus Pengawasan
             Sesuai design.md §3.5 & §5 (Navy Dark Mode, font Playfair Display)
        ============================================================ --}}
        <x-judul-halaman label="REPOSITORI PENGAWASAN KETENAGAKERJAAN" judul="Arsip Kasus Pengawasan"
            deskripsi="Dokumentasi permanen seluruh kasus penipuan lowongan kerja yang telah diproses hingga tahap akhir, termasuk berkas penetapan hukum dan surat laporan kepolisian resmi." />

        {{-- Kartu angka --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu-angka label="Total Kasus" :angka="$totalArsip" data-testid="stat-total-arsip" />
            <x-kartu-angka label="Surat Terbit" :angka="$jumlahSuratTerbit" :nada="WarnaBadge::surat(true)" data-testid="stat-surat-terbit" />
            <x-kartu-angka label="Tidak Ditindaklanjuti" :angka="$jumlahTidakDitindaklanjuti" :nada="WarnaBadge::status(StatusKasus::TidakDitindaklanjuti)" />
        </div>

        {{-- ============================================================
             2. FILTER & PENCARIAN
        ============================================================ --}}
        {{-- Filter Status Akhir (semua pilihan bergaya sama) --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach([
                [null, 'Semua Status', $totalArsip],
                [StatusKasus::Selesai->value, StatusKasus::Selesai->value, $jumlahSelesai],
                [StatusKasus::TidakDitindaklanjuti->value, StatusKasus::TidakDitindaklanjuti->value, $jumlahTidakDitindaklanjuti],
                [StatusKasus::TidakTerbukti->value, StatusKasus::TidakTerbukti->value, $jumlahTidakTerbukti],
            ] as [$nilai, $label, $jumlah])
                <x-pil-filter :href="route('arsip-kasus.index', array_filter(['status' => $nilai, 'cari' => request('cari')]))"
                    :aktif="$nilai ? request('status') === $nilai : ! request('status')">{{ $label }} ({{ $jumlah }})</x-pil-filter>
            @endforeach
        </div>

        {{-- Form Pencarian --}}
        <x-filter :action="route('arsip-kasus.index')">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <x-filter.cari placeholder="Cari tiket, lowongan, perusahaan...." />
        </x-filter>

        {{-- ============================================================
             3. TABEL DAFTAR KASUS ARSIP
        ============================================================ --}}
        @if($arsip->isEmpty())
            {{-- State: Filter Nihil atau Arsip Kosong --}}
            @if(request('cari') || request('status'))
                @php $keterangan = 'Tidak ditemukan kasus arsip yang cocok dengan kata kunci "' . request('cari') . '" atau filter status yang dipilih.'; @endphp
                <x-pesan-kosong judul="Tidak Ada Kasus Sesuai Kriteria Filter" :keterangan="$keterangan">
                    <x-tombol varian="sekunder" :href="route('arsip-kasus.index')">Reset Filter & Pencarian</x-tombol>
                </x-pesan-kosong>
            @else
                <x-pesan-kosong judul="Belum Ada Kasus yang Diarsipkan"
                    keterangan="Seluruh laporan yang telah diselesaikan (Selesai, Tidak Ditindaklanjuti, atau Tidak Terbukti) akan disimpan di repositori arsip ini secara otomatis." />
            @endif
        @else
            <div class="bg-white rounded-card shadow-lg border border-gray-100 p-6 sm:p-8">
                <x-tabel>
                    <x-slot:kepala>
                        <th class="whitespace-nowrap">Kasus / ID Tiket</th>
                        <th class="min-w-[12rem]">Lowongan & Perusahaan</th>
                        <th class="whitespace-nowrap">Status Akhir</th>
                        <th class="whitespace-nowrap">Dokumen Resmi Terbit</th>
                        <th class="whitespace-nowrap">Tanggal Arsip</th>
                        <th class="text-right whitespace-nowrap">Aksi</th>
                    </x-slot:kepala>
                            @foreach($arsip as $item)
                                @php
                                    $lowongan   = $item->lowonganKerja;
                                    $perusahaan = $lowongan?->perusahaan;
                                    $klas       = $item->hasilKlasifikasi;
                                    $tl         = $item->tindakLanjutKasus;
                                    $surat      = $tl?->suratTeguranLaporanPolisi;
                                    $status     = $item->status_pengaduan;
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td>
                                        <span class="font-mono font-bold text-teal-800 block whitespace-nowrap">
                                            {{ $item->nomor_tiket }}
                                        </span>
                                        @if($klas)
                                            <span class="text-[11px] text-gray-500 whitespace-nowrap">
                                                Skor: {{ number_format($klas->confidence_score, 0) }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-bold text-navy-900 block">
                                            {{ $lowongan->posisi_lowongan ?? '-' }}
                                        </span>
                                        <span class="text-gray-600">
                                            {{ $perusahaan->nama_perusahaan ?? 'Perusahaan Tidak Disebutkan' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <x-badge-status :status="$status" />
                                    </td>
                                    <td>
                                        @if($surat && $surat->status_penerbitan === 'Terbit')
                                            <span class="font-mono text-teal-800 font-bold block text-xs whitespace-nowrap">
                                                {{ $surat->nomor_surat }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 truncate block max-w-xs" title="{{ $surat->tujuan_instansi }}">
                                                {{ $surat->tujuan_instansi }}
                                            </span>
                                        @elseif($status === \App\Enums\StatusKasus::TidakTerbukti)
                                            <span class="text-gray-400 italic text-[11px] whitespace-nowrap">- (Ditutup Analis)</span>
                                        @else
                                            <span class="text-gray-400 italic text-[11px] whitespace-nowrap">- (Tidak Ada Surat)</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600 whitespace-nowrap">
                                        {{-- Tanggal kasus diselesaikan (Riwayat Kasus), bukan updated_at --}}
                                        {{ $item->tanggalSelesai()?->translatedFormat('d M Y') ?? '-' }}
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <x-tombol ukuran="sm" :href="route('arsip-kasus.show', $item->id_pengaduan)">Lihat Detail →</x-tombol>
                                    </td>
                                </tr>
                            @endforeach
                </x-tabel>

                {{-- Paginasi --}}
                <x-paginasi :daftar="$arsip" satuan="Kasus Arsip" />
            </div>
        @endif

    </div>
</x-app-layout>

