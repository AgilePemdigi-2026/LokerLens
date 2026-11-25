<x-app-layout>
    <div class="max-w-5xl mx-auto py-4 space-y-6">

        @php
            $lowongan   = $pengaduan->lowonganKerja;
            $perusahaan = $lowongan?->perusahaan;
            $klasifikasi = $pengaduan->hasilKlasifikasi;
            $tinjauan   = $klasifikasi?->tinjauanAnalis;
            $tl         = $pengaduan->tindakLanjutKasus;
            $status     = $pengaduan->status_pengaduan;
        @endphp

        {{-- Breadcrumb --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-400">
            <div class="flex items-center space-x-2">
                <a href="{{ route('arsip-kasus.index') }}" class="hover:text-teal-400 transition font-medium">← Kembali ke Daftar Arsip</a>
                <span>›</span>
                <span class="text-gray-300">Arsip Kasus</span>
                <span>›</span>
                <span class="text-white font-medium">{{ $pengaduan->nomor_tiket }}</span>
            </div>
            <x-badge :nada="\App\Support\WarnaBadge::ABU_GELAP">Kasus Ditutup Permanen</x-badge>
        </div>

        {{-- 1. Header Kasus --}}
        <div class="bg-navy-900 rounded-card p-6 sm:p-8 text-white border border-navy-800 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-teal-400">
                    REKAM ARSIP PENGAWASAN RESMI
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold mt-1">
                    {{ $lowongan->posisi_lowongan ?? 'Posisi Tidak Disebutkan' }}
                </h1>
                <p class="text-xs sm:text-sm text-gray-300 mt-1">
                    Perusahaan Terlapor: <strong class="text-white">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                    • ID Kasus: <span class="font-mono text-teal-300">{{ $pengaduan->nomor_tiket }}</span>
                </p>
            </div>
            <div class="shrink-0 text-left md:text-right">
                <span class="text-xs text-gray-400 block mb-1">Status Akhir</span>
                <x-badge-status :status="$status" />
            </div>
        </div>

        {{-- Grid 2 Kolom --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri (2 Kolom): Dokumen Surat, Penetapan, & Informasi Lowongan --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Dokumen Surat Resmi yang Diterbitkan --}}
                <div class="bg-white rounded-card shadow-lg border border-gray-100 p-6 text-navy-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h2 class="text-base font-bold text-navy-900 flex items-center gap-2">
                            <span class="text-lg">🚔</span>
                            <span>Dokumen Surat Resmi</span>
                        </h2>
                        @if($surat && $surat->status_penerbitan === 'Terbit')
                            <x-badge :nada="\App\Support\WarnaBadge::surat(true)">Terbit Resmi</x-badge>
                        @else
                            <x-badge>Tidak Diterbitkan</x-badge>
                        @endif
                    </div>

                    @if($surat && $surat->status_penerbitan === 'Terbit')
                        <div class="p-4 bg-teal-50/50 rounded-xl border border-teal-200 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-gray-500 block">Nomor Surat:</span>
                                <strong class="font-mono text-teal-900 text-sm">{{ $surat->nomor_surat }}</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Jenis Dokumen:</span>
                                <strong class="text-navy-900">{{ $surat->jenis_dokumen }}</strong>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Instansi Tujuan:</span>
                                <span class="text-navy-900 font-medium">{{ $surat->tujuan_instansi }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Tanggal Terbit:</span>
                                <span class="text-navy-900 font-medium">
                                    {{ $surat->tanggal_terbit ? $surat->tanggal_terbit->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}
                                </span>
                            </div>
                            @if($surat->kronologi_kejadian)
                                <div class="sm:col-span-2 pt-2 border-t border-teal-200">
                                    <span class="text-gray-500 block mb-1">Kronologi Kejadian Resmi:</span>
                                    <p class="text-gray-800 leading-relaxed italic bg-white p-3 rounded border border-teal-100 text-xs">
                                        "{{ $surat->kronologi_kejadian }}"
                                    </p>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end">
                            <x-tombol :href="route('buat-surat.terbit', ['pengaduanId' => $pengaduan->id_pengaduan, 'suratId' => $surat->id_surat])">
                                <span>Buka Lembar Surat Resmi</span>
                                <span>↗</span>
                            </x-tombol>
                        </div>
                    @else
                        <div class="p-6 text-center text-xs text-gray-400 space-y-2">
                            <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-lg">
                                📄
                            </div>
                            <p>
                                @if($status === \App\Enums\StatusKasus::TidakTerbukti)
                                    Kasus ditutup oleh Analis dengan hasil "Tidak Terbukti" — tidak ada surat yang diterbitkan.
                                @elseif($status === \App\Enums\StatusKasus::TidakDitindaklanjuti)
                                    Kasus ditandai tidak ditindaklanjuti dan diarsipkan — tidak ada surat yang diterbitkan.
                                @else
                                    Tidak ada dokumen surat resmi yang diterbitkan untuk kasus ini.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Ringkasan Penetapan Keputusan --}}
                <div class="bg-white rounded-card shadow-lg border border-gray-100 p-6 text-navy-900 space-y-4">
                    <h2 class="text-base font-bold text-navy-900 border-b border-gray-100 pb-3">
                        Ringkasan Penetapan & Hasil Investigasi
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        @if($tl)
                            <div>
                                <span class="text-gray-500 block">Keputusan Kepala Bidang:</span>
                                <span class="font-bold {{ $tl->keputusan === 'Tindaklanjuti' ? 'text-risk-low' : 'text-slate-600' }} text-sm">
                                    {{ $tl->keputusan }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Pejabat Pengambil Keputusan:</span>
                                <span class="font-semibold text-navy-900">
                                    {{ $tl->kepalaBidang?->name ?? '-' }} (Kepala Bidang)
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Tanggal Keputusan:</span>
                                <span class="text-navy-900 font-medium">
                                    {{ $tl->tanggal_keputusan ? $tl->tanggal_keputusan->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}
                                </span>
                            </div>
                            @if($tl->catatan_keputusan)
                                <div class="sm:col-span-2">
                                    <span class="text-gray-500 block mb-1">Catatan Keputusan:</span>
                                    <p class="text-gray-700 bg-gray-50 p-3 rounded border border-gray-200 leading-relaxed italic">
                                        "{{ $tl->catatan_keputusan }}"
                                    </p>
                                </div>
                            @endif
                        @elseif($status === \App\Enums\StatusKasus::TidakTerbukti)
                            <div class="sm:col-span-2">
                                <span class="text-gray-500 block">Keputusan:</span>
                                <span class="font-bold text-slate-600 text-sm">Tidak Terbukti (Ditutup oleh Analis)</span>
                            </div>
                        @elseif(! $tinjauan)
    <div class="sm:col-span-2 text-center text-gray-400 py-4">
        Kasus ditutup otomatis oleh sistem karena skor di bawah ambang prioritas.
        Tidak ada tinjauan analis maupun keputusan Kepala Bidang.
    </div>
                        @endif

                        @if($tinjauan)
                            <div class="sm:col-span-2 {{ $tl ? 'pt-2 border-t border-gray-200' : '' }}">
                                <span class="text-gray-500 block mb-1">Catatan Hasil Audit Analis:</span>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded border border-gray-200 leading-relaxed italic">
                                    "{{ $tinjauan->catatan_tinjauan }}"
                                </p>
                                <p class="text-[11px] text-gray-400 mt-1">
                                    Analis: {{ $tinjauan->analis?->name ?? '-' }}
                                    • Hasil: {{ $tinjauan->hasil_tinjauan }}
                                    • {{ $tinjauan->tanggal_tinjauan ? $tinjauan->tanggal_tinjauan->translatedFormat('d M Y, H:i') . ' WIB' : '' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Informasi Lowongan yang Dilaporkan --}}
                <div class="bg-white rounded-card shadow-lg border border-gray-100 p-6 text-navy-900 space-y-3">
                    <h2 class="text-base font-bold text-navy-900 border-b border-gray-100 pb-3">
                        Informasi Lowongan yang Dilaporkan
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-gray-500 block">Posisi Lowongan:</span>
                            <strong class="text-navy-900">{{ $lowongan->posisi_lowongan ?? '-' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Perusahaan:</span>
                            <strong class="text-navy-900">{{ $perusahaan->nama_perusahaan ?? '-' }}</strong>
                        </div>
                        
                        <div>
                            <span class="text-gray-500 block">Link Lowongan:</span>
                            @if($lowongan->link_lowongan)
                                <a href="{{ $lowongan->link_lowongan }}" target="_blank" class="text-teal-600 hover:underline break-all">
                                    {{ $lowongan->link_lowongan }}
                                </a>
                            @else
                                <span class="text-gray-400 italic">Tidak dilampirkan</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-500 block text-xs mb-1">Kutipan Deskripsi Lowongan:</span>
                        <div class="bg-gray-50 p-3 rounded border border-gray-200 text-xs text-gray-800 leading-relaxed max-h-40 overflow-y-auto whitespace-pre-line">{{ $lowongan->deskripsi_lowongan ?? '-' }}</div>
                    </div>

                    {{-- Bukti Pendukung --}}
                    @if($pengaduan->buktiPendukung && $pengaduan->buktiPendukung->count() > 0)
                        <div class="pt-3 border-t border-gray-200">
                            <span class="text-xs font-semibold text-gray-600 block mb-2">
                                Bukti Pendukung Terlampir ({{ $pengaduan->buktiPendukung->count() }} Berkas)
                            </span>
                            <div class="space-y-1.5">
                                @foreach($pengaduan->buktiPendukung as $bukti)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 border border-gray-200 rounded text-xs">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <span class="w-7 h-7 rounded bg-navy-900 text-teal-400 font-bold uppercase text-[10px] flex items-center justify-center shrink-0">
                                                {{ $bukti->tipe_file }}
                                            </span>
                                            <span class="truncate text-navy-900 font-medium">{{ $bukti->nama_file }}</span>
                                        </div>
                                        <span class="flex items-center gap-2 text-[10px] text-gray-500 shrink-0">
                                            {{ round($bukti->ukuran_file / 1024, 1) }} KB
                                            {{-- Berkas di disk private: dibuka lewat route yang dicek perannya --}}
                                            <a href="{{ route('bukti.lihat', $bukti) }}" target="_blank" rel="noopener" class="font-semibold text-teal-700 hover:underline">
                                                {{ \App\Constants\LokerLensCopy::BTN_LIHAT_BUKTI }}
                                            </a>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Kolom Kanan (1 Kolom): Riwayat Kasus (Timeline) --}}
            <div class="space-y-6">

                {{-- Garis Waktu Riwayat Kasus (BR-11) --}}
                <div class="bg-white rounded-card shadow-lg border border-gray-100 p-6 text-navy-900 space-y-4">
                    <div class="border-b border-gray-100 pb-3">
                        <h2 class="text-sm font-bold text-navy-900">
                            Garis Waktu Riwayat Kasus
                        </h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Jejak audit kronologis perubahan status
                        </p>
                    </div>

                    @if($pengaduan->riwayatKasus && $pengaduan->riwayatKasus->count() > 0)
                        <div class="relative pl-6 space-y-5 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-200">
                            @foreach($pengaduan->riwayatKasus->reverse() as $riwayat)
                                <div class="relative">
                                    <div class="absolute -left-6 top-0 w-4 h-4 rounded-full {{ \App\Support\WarnaBadge::titik(\App\Support\WarnaBadge::status($riwayat->status_kasus)) }} border-2 border-white shadow"></div>
                                    <span class="text-[10px] text-gray-400 block font-mono">
                                        {{ $riwayat->tanggal_update ? $riwayat->tanggal_update->translatedFormat('d M Y, H:i') . ' WIB' : '-' }}
                                    </span>
                                    <strong class="text-xs text-navy-900 block font-bold">{{ $riwayat->status_kasus }}</strong>
                                    <p class="text-[11px] text-gray-600 mt-0.5">{{ $riwayat->keterangan }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 text-center py-4">Belum ada riwayat perubahan status.</p>
                    @endif
                </div>

                {{-- Metadata Arsip --}}
                <div class="bg-gray-50 rounded-card p-4 border border-gray-200 text-xs text-gray-600 space-y-2">
                    <div class="font-bold text-navy-900">Integritas Data Arsip</div>
                    <p class="text-[11px] text-gray-500 leading-relaxed">
                        Data arsip ini bersifat <em>read-only</em> dan dilindungi integritasnya
(riwayat kasus tidak dapat dihapus atau diubah setelah kasus ditutup).
                    </p>
                    <div class="pt-2 border-t border-gray-200 space-y-1 text-[11px]">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tanggal Pengaduan:</span>
                            <span class="text-navy-900 font-medium">
                                {{ $pengaduan->tanggal_pengaduan ? $pengaduan->tanggal_pengaduan->translatedFormat('d M Y') : '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tanggal Arsip:</span>
                            <span class="text-navy-900 font-medium">
                                {{-- Tanggal kasus diselesaikan (Riwayat Kasus), bukan updated_at --}}
                                {{ $pengaduan->tanggalSelesai()?->translatedFormat('d M Y') ?? '-' }}
                            </span>
                        </div>
                        @if($klasifikasi)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Probabilitas Penipuan:</span>
                                <span class="font-bold text-navy-900">{{ number_format($klasifikasi->confidence_score, 1) }}%</span>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

