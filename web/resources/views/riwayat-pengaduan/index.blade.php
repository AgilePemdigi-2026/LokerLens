@use('App\Constants\LokerLensCopy')
@use('App\Enums\StatusKasus')
@use('App\Support\WarnaBadge')

{{-- Riwayat Pengaduan — hanya pengaduan yang statusnya sudah akhir (issue #25). --}}
<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             HEADER BANNER — Judul & Statistik Ringkasan
             Sesuai design.md §3: label teal uppercase, judul serif navy
        ============================================================ --}}
        <x-judul-halaman label="REKAM JEJAK PENGAWASAN" :deskripsi="LokerLensCopy::RIWAYAT_HERO_DESKRIPSI">
            <x-slot:judul>Riwayat <span class="font-serif italic text-teal-300">Pengaduan</span></x-slot:judul>
        </x-judul-halaman>

        {{-- Kartu angka: Total Selesai = Ditindaklanjuti (Selesai) + Ditutup (status akhir lain) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kartu-angka :label="LokerLensCopy::RIWAYAT_STAT_TOTAL" :angka="$totalSelesai" />
            <x-kartu-angka :label="LokerLensCopy::RIWAYAT_STAT_DITINDAKLANJUTI" :angka="$ditindaklanjuti" :nada="WarnaBadge::status(StatusKasus::Selesai)" />
            <x-kartu-angka :label="LokerLensCopy::RIWAYAT_STAT_DITUTUP" :angka="$ditutup" :nada="WarnaBadge::status(StatusKasus::TidakDitindaklanjuti)" />
        </div>

        {{-- ============================================================
             BILAH PENCARIAN & FILTER — Sesuai design.md §3.2 & screen_06.jpg
        ============================================================ --}}
        <x-filter :action="route('riwayat-pengaduan.index')">
            <x-filter.cari placeholder="Cari nomor tiket, posisi, atau nama perusahaan..." />

            {{-- Filter Status: hanya 4 status akhir (disiapkan controller) --}}
            <x-filter.pilih name="status">
                <option value="">{{ LokerLensCopy::RIWAYAT_SEMUA_STATUS }}</option>
                @foreach($pilihanStatus as $status)
                    <option value="{{ $status->value }}"
                            {{ request('status') === $status->value ? 'selected' : '' }}>
                        {{ $status->value }}
                    </option>
                @endforeach
            </x-filter.pilih>

            <x-slot:tambahan>
                {{-- Reset filter jika ada --}}
                @if(request('cari') || request('status'))
                    <x-tombol varian="sekunder" :href="route('riwayat-pengaduan.index')" class="w-full md:w-auto">Reset</x-tombol>
                @endif
                <x-tombol varian="aksi" :href="route('periksa-lowongan.index')" class="w-full md:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ LokerLensCopy::BTN_PERIKSA_LOWONGAN_BARU }}</span>
                </x-tombol>
            </x-slot:tambahan>
        </x-filter>

        {{-- ============================================================
             DAFTAR PENGADUAN — Daftar kartu responsif / kondisi kosong
        ============================================================ --}}
        <div>
            {{-- Label jumlah hasil. Angka berwarna terang karena latar halaman gelap
                 (sebelumnya navy sehingga angkanya tidak terlihat). --}}
            @if(request('cari') || request('status'))
                <p id="label-jumlah-riwayat" class="text-xs text-gray-400 mb-3 px-1">
                    Menampilkan <strong class="text-white">{{ $pengaduan->total() }}</strong>
                    hasil
                    @if(request('cari')) untuk "<strong class="text-white">{{ request('cari') }}</strong>"@endif
                    @if(request('status')) dengan status <strong class="text-white">{{ request('status') }}</strong>@endif
                </p>
            @else
                <p id="label-jumlah-riwayat" class="text-xs text-gray-400 mb-3 px-1">
                    {{ LokerLensCopy::RIWAYAT_MENAMPILKAN_SEMUA }} · <strong class="text-white">{{ $pengaduan->total() }}</strong> {{ LokerLensCopy::RIWAYAT_SATUAN_PENGADUAN }}
                </p>
            @endif

            {{-- ======================================================
                 KONDISI KOSONG — belum ada pengaduan selesai, atau pencarian tidak cocok
            ====================================================== --}}
            @if($pengaduan->isEmpty())
                @if(request('cari') || request('status'))
                    <x-pesan-kosong judul="Data Lowongan Tidak Ditemukan"
                        keterangan="Tidak ada pengaduan yang sesuai dengan kata kunci atau filter yang dipilih.">
                        <x-tombol varian="sekunder" :href="route('riwayat-pengaduan.index')">← Lihat Semua Pengaduan</x-tombol>
                    </x-pesan-kosong>
                @else
                    {{-- Belum ada pengaduan selesai: arahkan ke Status Pengaduan --}}
                    <x-pesan-kosong :judul="LokerLensCopy::BELUM_ADA_PENGADUAN_SELESAI" :keterangan="LokerLensCopy::BELUM_ADA_PENGADUAN_SELESAI_KETERANGAN">
                        <x-tombol :href="route('status-pengaduan.index')">
                            <span>{{ LokerLensCopy::BTN_PANTAU_STATUS }}</span>
                            <span aria-hidden="true">→</span>
                        </x-tombol>
                    </x-pesan-kosong>
                @endif

            @else
                {{-- ======================================================
                     DAFTAR KARTU PENGADUAN — Satu kartu per pengaduan
                ====================================================== --}}
                <div class="space-y-3">
                    @foreach($pengaduan as $item)
                        @php
                            $low   = $item->lowonganKerja;
                            $per   = $low->perusahaan ?? null;
                            $klas  = $item->hasilKlasifikasi;
                            $score = $klas ? (int) $klas->confidence_score : null;
                            $status = $item->status_pengaduan;

                        @endphp

                        <div class="bg-white rounded-card shadow-lg border border-gray-100 hover:shadow-xl transition">
                            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">

                                {{-- Kolom Kiri: Nomor Tiket & Tanggal --}}
                                <div class="shrink-0 w-full sm:w-40">
                                    <x-nomor-tiket>{{ $item->nomor_tiket }}</x-nomor-tiket>
                                    <p class="text-[11px] text-gray-400 mt-1.5 leading-tight">
                                        {{ $item->tanggal_pengaduan->translatedFormat('d M Y') }}<br>
                                        {{ $item->tanggal_pengaduan->format('H:i') }} WIB
                                    </p>
                                </div>

                                {{-- Kolom Tengah: Info Lowongan --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-navy-900 truncate">
                                        {{ $low->posisi_lowongan ?? '-' }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                                        {{ $per->nama_perusahaan ?? '-' }}
                                        @if($low->lokasi ?? null)
                                            <span class="text-gray-300 mx-1">•</span>
                                            {{ $low->lokasi }}
                                        @endif
                                    </p>
                                    {{-- Status --}}
                                    <x-badge-status :status="$status" class="mt-2" />
                                </div>

                                {{-- Kolom Kanan: Skor & Tombol Lihat Detail / Lihat Hasil --}}
                                <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 sm:gap-2 shrink-0">
                                    @if($score !== null)
                                        <div class="text-right">
                                            <p class="text-[10px] uppercase tracking-widest font-semibold text-gray-400 leading-none">
                                                {{ LokerLensCopy::LABEL_SKOR_RISIKO }}
                                            </p>
                                            <p class="text-2xl font-extrabold text-navy-900 leading-tight mt-0.5">
                                                {{ $score }}<span class="text-sm font-bold text-gray-500">%</span>
                                            </p>
                                            <x-badge :nada="WarnaBadge::risiko($klas->tingkat_risiko)">{{ $klas->label_risiko }}</x-badge>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum diproses</span>
                                    @endif

                                    <div class="mt-auto flex items-center gap-2">
                                        {{-- Detail progres penanganan (halaman Status Pengaduan) --}}
                                        <x-tombol ukuran="sm" :href="route('status-pengaduan.detail', $item->id_pengaduan)">{{ LokerLensCopy::BTN_LIHAT_DETAIL }}</x-tombol>
                                        <x-tombol ukuran="sm" varian="sekunder" :href="route('periksa-lowongan.hasil', $item->id_pengaduan)">
                                            <span>Lihat Hasil</span>
                                            <span aria-hidden="true">→</span>
                                        </x-tombol>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ======================================================
                     PAGINATION — Laravel Tailwind pagination
                ====================================================== --}}
                @if($pengaduan->hasPages())
                    <div class="mt-6 bg-white rounded-card border border-gray-100 shadow-lg p-4">
                        {{ $pengaduan->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-app-layout>

