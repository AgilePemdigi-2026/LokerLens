<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             1. HERO BANNER — Repositori Arsip Kasus Pengawasan
             Sesuai design.md §3.5 & §5 (Navy Dark Mode, font Playfair Display)
        ============================================================ --}}
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-7 sm:p-9 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-80 h-80 bg-teal-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                        REPOSITORI PENGAWASAN KETENAGAKERJAAN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        Arsip <span class="font-serif italic text-teal-300">Kasus Pengawasan</span>
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 max-w-xl leading-relaxed">
                        Dokumentasi permanen seluruh kasus penipuan lowongan kerja yang telah diproses hingga tahap akhir,
                        termasuk berkas penetapan hukum dan surat laporan kepolisian resmi.
                    </p>
                </div>

                {{-- Mini Stat Summary Chips --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 shrink-0 text-xs">
                    <div class="p-3 bg-navy-800/80 rounded-xl border border-navy-700">
                        <span class="text-gray-400 block text-[11px]">Total Kasus</span>
                        <span class="text-lg font-bold font-mono text-white" data-testid="stat-total-arsip">{{ $totalArsip }} Kasus</span>
                    </div>
                    <div class="p-3 bg-navy-800/80 rounded-xl border border-navy-700">
                        <span class="text-gray-400 block text-[11px]">Surat Terbit</span>
                        <span class="text-lg font-bold font-mono text-emerald-400" data-testid="stat-surat-terbit">{{ $jumlahSuratTerbit }} Dokumen</span>
                    </div>
                    <div class="p-3 bg-navy-800/80 rounded-xl border border-navy-700 col-span-2 sm:col-span-1">
                        <span class="text-gray-400 block text-[11px]">Tidak Ditindaklanjuti</span>
                        <span class="text-lg font-bold font-mono text-amber-400">{{ $jumlahTidakDitindaklanjuti }} Kasus</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             2. FILTER & PENCARIAN
        ============================================================ --}}
        <div class="bg-white rounded-card shadow-sm p-4 border border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4">
            {{-- Filter Status Akhir --}}
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                <a href="{{ route('arsip-kasus.index', array_filter(['cari' => request('cari')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ !request('status') ? 'bg-navy-900 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Semua Status ({{ $totalArsip }})
                </a>
                <a href="{{ route('arsip-kasus.index', array_filter(['status' => \App\Enums\StatusKasus::Selesai->value, 'cari' => request('cari')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ request('status') === \App\Enums\StatusKasus::Selesai->value ? 'bg-emerald-700 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Selesai ({{ $jumlahSelesai }})
                </a>
                <a href="{{ route('arsip-kasus.index', array_filter(['status' => \App\Enums\StatusKasus::TidakDitindaklanjuti->value, 'cari' => request('cari')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ request('status') === \App\Enums\StatusKasus::TidakDitindaklanjuti->value ? 'bg-gray-800 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Tidak Ditindaklanjuti ({{ $jumlahTidakDitindaklanjuti }})
                </a>
                <a href="{{ route('arsip-kasus.index', array_filter(['status' => \App\Enums\StatusKasus::TidakTerbukti->value, 'cari' => request('cari')])) }}"
                   class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ request('status') === \App\Enums\StatusKasus::TidakTerbukti->value ? 'bg-slate-700 text-white shadow-xs' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Tidak Terbukti ({{ $jumlahTidakTerbukti }})
                </a>
            </div>

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('arsip-kasus.index') }}" class="w-full md:w-80 flex items-center gap-2">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="relative flex-1">
                    <input type="text" name="cari" value="{{ request('cari') }}"
                           placeholder="Cari tiket, lowongan, perusahaan, nomor surat..."
                           class="w-full pl-9 pr-4 py-2 rounded-control border border-gray-300 text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="px-3.5 py-2 bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold rounded-control transition shrink-0">
                    Cari
                </button>
            </form>
        </div>

        {{-- ============================================================
             3. TABEL DAFTAR KASUS ARSIP
        ============================================================ --}}
        @if($arsip->isEmpty())
            {{-- State: Filter Nihil atau Arsip Kosong --}}
            @if(request('cari') || request('status'))
                <div class="bg-white rounded-card shadow-md border border-gray-200 p-12 text-center text-navy-900 space-y-3">
                    <div class="w-14 h-14 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center mx-auto text-2xl font-bold">
                        🔍
                    </div>
                    <h3 class="text-base font-bold text-navy-900">Tidak Ada Kasus Sesuai Kriteria Filter</h3>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Tidak ditemukan kasus arsip yang cocok dengan kata kunci "{{ request('cari') }}" atau filter status yang dipilih.
                    </p>
                    <a href="{{ route('arsip-kasus.index') }}"
                       class="inline-block mt-2 px-4 py-2 rounded bg-teal-600 text-white font-semibold text-xs transition hover:bg-teal-500">
                        Reset Filter & Pencarian
                    </a>
                </div>
            @else
                <div class="bg-white rounded-card shadow-md border border-gray-200 p-12 text-center text-navy-900 space-y-3">
                    <div class="w-14 h-14 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-2xl">
                        📁
                    </div>
                    <h3 class="text-base font-bold text-navy-900">Belum Ada Kasus yang Diarsipkan</h3>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Seluruh laporan yang telah diselesaikan (Selesai, Tidak Ditindaklanjuti, atau Tidak Terbukti) akan disimpan di repositori arsip ini secara otomatis.
                    </p>
                </div>
            @endif
        @else
            <div class="bg-white rounded-card shadow-md border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold border-b border-gray-200">
                            <tr>
                                <th class="py-3.5 px-4">Kasus / ID Tiket</th>
                                <th class="py-3.5 px-4">Lowongan & Perusahaan</th>
                                <th class="py-3.5 px-4">Status Akhir</th>
                                <th class="py-3.5 px-4">Dokumen Resmi Terbit</th>
                                <th class="py-3.5 px-4">Tanggal Arsip</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($arsip as $item)
                                @php
                                    $lowongan   = $item->lowonganKerja;
                                    $perusahaan = $lowongan?->perusahaan;
                                    $klas       = $item->hasilKlasifikasi;
                                    $tl         = $item->tindakLanjutKasus;
                                    $surat      = $tl?->suratTeguranLaporanPolisi;
                                    $status     = $item->status_pengaduan;
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4">
                                        <span class="font-mono font-bold text-navy-900 block text-xs">
                                            {{ $item->nomor_tiket }}
                                        </span>
                                        @if($klas)
                                            <span class="text-[11px] text-gray-500">
                                                Skor: {{ number_format($klas->confidence_score, 1) }}%
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-bold text-navy-900 block">
                                            {{ $lowongan->posisi_lowongan ?? '-' }}
                                        </span>
                                        <span class="text-gray-600">
                                            {{ $perusahaan->nama_perusahaan ?? 'Perusahaan Tidak Disebutkan' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($status === \App\Enums\StatusKasus::Selesai)
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                {{ $status->value }}
                                            </span>
                                        @elseif($status === \App\Enums\StatusKasus::TidakDitindaklanjuti)
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 border border-gray-300 inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                                {{ $status->value }}
                                            </span>
                                        @elseif($status === \App\Enums\StatusKasus::TidakTerbukti)
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300 inline-flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                {{ $status->value }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 border border-gray-300">
                                                {{ $status instanceof \App\Enums\StatusKasus ? $status->value : $status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($surat && $surat->status_penerbitan === 'Terbit')
                                            <span class="font-mono text-teal-800 font-bold block text-xs">
                                                {{ $surat->nomor_surat }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 truncate block max-w-xs" title="{{ $surat->tujuan_instansi }}">
                                                {{ $surat->tujuan_instansi }}
                                            </span>
                                        @elseif($status === \App\Enums\StatusKasus::TidakTerbukti)
                                            <span class="text-gray-400 italic text-[11px]">- (Ditutup Analis)</span>
                                        @else
                                            <span class="text-gray-400 italic text-[11px]">- (Tidak Ada Surat)</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-gray-600">
                                        {{ $item->updated_at ? $item->updated_at->translatedFormat('d M Y') : '-' }}
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <a href="{{ route('arsip-kasus.show', $item->id_pengaduan) }}"
                                           class="inline-block px-3 py-1.5 rounded bg-navy-900 hover:bg-navy-800 text-white font-semibold transition text-xs shadow-xs">
                                            Buka Detail →
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                <div class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                    <div>
                        Menampilkan {{ $arsip->firstItem() ?? 0 }} - {{ $arsip->lastItem() ?? 0 }} dari {{ $arsip->total() }} Kasus Arsip
                    </div>
                    <div>
                        {{ $arsip->links() }}
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>

