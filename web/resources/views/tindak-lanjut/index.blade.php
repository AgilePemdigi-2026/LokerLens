<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Banner Gelap Pusat Penanganan Laporan (Bagian 6.5.1) -->
        <x-judul-halaman label="PUSAT PENANGANAN LAPORAN" judul="Daftar Laporan"
            deskripsi="Tinjau laporan yang telah diperiksa analis dan tetapkan keputusan tindak lanjut kasus.">
            <!-- Kotak Angka Antrean Keputusan -->
            <div class="bg-navy-800/80 border border-navy-800 rounded-control p-4 sm:p-5 flex items-center space-x-4">
                <div class="text-4xl font-extrabold text-teal-400">
                    {{ sprintf('%02d', $jumlahMenunggu) }}
                </div>
                <div>
                    <span class="text-xs font-bold text-white block uppercase tracking-wider">
                        Menunggu Keputusan
                    </span>
                    {{-- §6.5.1: rincian "2 Prioritas tinggi · 1 Prioritas sedang" --}}
                    <span class="text-[11px] text-gray-400">
                        {{ $jumlahPrioritasTinggi }} Prioritas tinggi · {{ $jumlahPrioritasSedang }} Prioritas sedang
                    </span>
                </div>
            </div>
        </x-judul-halaman>

        <!-- Filter & Pencarian Laporan (§6.5.1: pencarian, filter Status, filter Prioritas) -->
        <x-filter :action="route('tindak-lanjut.index')">
            <x-filter.cari label="Pencarian" placeholder="Cari laporan, posisi, atau perusahaan..." />

            <x-filter.pilih name="status" label="Status">
                <option value="">Semua Status</option>
                @foreach(\App\Enums\StatusKasus::tahapKepalaBidang() as $status)
                    <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->value }}</option>
                @endforeach
            </x-filter.pilih>

            <x-filter.pilih name="prioritas" label="Prioritas">
                <option value="">Semua Prioritas</option>
                <option value="tinggi" {{ request('prioritas') === 'tinggi' ? 'selected' : '' }}>Prioritas Tinggi</option>
                <option value="sedang" {{ request('prioritas') === 'sedang' ? 'selected' : '' }}>Prioritas Sedang</option>
            </x-filter.pilih>
        </x-filter>

        <!-- Seksi Antrean Keputusan: Daftar Kartu Laporan (Bagian 6.5.1) -->
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-bold text-white tracking-tight">
                    Semua Laporan Kepala Bidang
                </h2>
                <span class="text-xs text-gray-400">
                    Menampilkan {{ $laporan->count() }} kasus ({{ $jumlahMenunggu }} menunggu keputusan)
                </span>
            </div>

            @forelse($laporan as $kasus)
                @php
                    $lowongan = $kasus->lowonganKerja;
                    $perusahaan = $lowongan->perusahaan ?? null;
                    $klasifikasi = $kasus->hasilKlasifikasi;
                    $score = $klasifikasi ? $klasifikasi->confidence_score : 0;
                    $isApproved = $kasus->status_pengaduan === \App\Enums\StatusKasus::TindakLanjutDisetujui;
                    // Kasus yang suratnya sudah disimpan sebagai draf tetapi belum terbit (issue #8)
                    $adaDraf = $kasus->tindakLanjutKasus?->suratTeguranLaporanPolisi?->status_penerbitan === 'Draft';
                @endphp
                <div class="bg-white rounded-card shadow-lg p-5 sm:p-6 border border-gray-100 hover:shadow-xl transition flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-nomor-tiket>{{ $kasus->nomor_tiket }}</x-nomor-tiket>
                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                {{ $kasus->tanggal_pengaduan->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                            <x-badge-status :status="$kasus->status_pengaduan" />
                            @if($adaDraf)
                                <x-badge :nada="\App\Support\WarnaBadge::surat(false)" data-testid="tanda-ada-draf">{{ \App\Constants\LokerLensCopy::TANDA_ADA_DRAF }}</x-badge>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-navy-900 leading-snug">
                            {{ $lowongan->posisi_lowongan ?? '-' }}
                        </h3>

                        <div class="text-xs text-gray-600 flex items-center gap-2">
                            <strong class="text-navy-900">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                            <span>•</span>
                            <span>Pelapor: {{ $kasus->pengguna->name ?? '-' }}</span>
                        </div>

                        <!-- Temuan Ringkas -->
                        <p class="text-xs text-gray-500 bg-gray-50 p-2.5 rounded border border-gray-200">
                            <span class="line-clamp-2"><strong>Temuan Analis:</strong> {{ $klasifikasi?->tinjauanAnalis?->catatan_tinjauan ?: '-' }}</span>
                        </p>
                    </div>

                    <!-- Right Side: Confidence Score & Action Button -->
                    <div class="flex flex-wrap sm:flex-col md:items-end justify-between items-center gap-3 shrink-0 md:w-72 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100">
                        <div class="text-left md:text-right">
                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wider block">Skor Kecurigaan</span>
                            {{-- Warna mengikuti tingkat risiko (<50 rendah, 50–69 sedang, ≥70 tinggi) --}}
                    
                            <div class="text-2xl font-extrabold {{ $klasifikasi?->warna_risiko['teks'] ?? 'text-gray-700' }}">
                                {{ number_format($score, 0) }}%
                            </div>
                            @if($klasifikasi)
                                <x-badge :nada="\App\Support\WarnaBadge::risiko($klasifikasi->tingkat_risiko)">Prioritas {{ $klasifikasi->tingkat_risiko }}</x-badge>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 md:justify-end">
                            <x-tombol ukuran="sm" :href="route('tindak-lanjut.detail', $kasus->id_pengaduan)">Lihat Detail →</x-tombol>
                            @if($isApproved)
                                <x-tombol ukuran="sm" varian="aksi" :href="route('buat-surat.index', $kasus->id_pengaduan)"
                                    title="{{ \App\Constants\LokerLensCopy::JUDUL_BUAT_LAPORAN }} / {{ \App\Constants\LokerLensCopy::JUDUL_BUAT_TEGURAN }}">
                                    {{ \App\Constants\LokerLensCopy::BTN_BUAT_SURAT }}
                                </x-tombol>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <x-pesan-kosong judul="Belum Ada Laporan yang Menunggu Keputusan"
                    keterangan="Semua laporan dari analis telah diproses atau belum ada eskalasi baru." />
            @endforelse

            @if($laporan->hasPages())
                <div class="bg-white rounded-card border border-gray-100 shadow-lg p-4">
                    {{ $laporan->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
