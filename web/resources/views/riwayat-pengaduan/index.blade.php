<x-app-layout>
    <div class="space-y-6 py-2">

        {{-- ============================================================
             HEADER BANNER — Judul & Statistik Ringkasan
             Sesuai design.md §3: label teal uppercase, judul serif navy
        ============================================================ --}}
        <div class="bg-navy-900 border border-navy-800 rounded-2xl p-7 sm:p-10 text-white shadow-2xl relative overflow-hidden">
            {{-- Dekoratif bulatan teal (konsisten dengan Beranda) --}}
            <div class="absolute right-0 top-0 w-80 h-80 bg-teal-600/5 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                {{-- Judul --}}
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2">
                        REKAM JEJAK PENGAWASAN
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        Riwayat <span class="font-serif italic text-teal-300">Pengaduan</span>
                    </h1>
                    <p class="text-sm text-gray-400 mt-2 max-w-lg leading-relaxed">
                        Daftar seluruh pengaduan lowongan kerja mencurigakan yang pernah Anda ajukan
                        beserta hasil pemeriksaan dan status akhirnya.
                    </p>
                </div>

                {{-- Kartu Statistik Ringkasan (3 metrik) --}}
                <div class="grid grid-cols-3 gap-3 shrink-0">
                    <div class="bg-navy-800/80 border border-navy-700 rounded-xl p-4 text-center min-w-[90px]">
                        <p class="text-2xl font-extrabold text-white">
                            {{ $pengaduan->total() }}
                        </p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mt-1 leading-tight">Total Laporan</p>
                    </div>
                    <div class="bg-navy-800/80 border border-navy-700 rounded-xl p-4 text-center min-w-[90px]">
                        <p class="text-2xl font-extrabold text-teal-300">
                            {{ $sedangBerjalan }}
                        </p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mt-1 leading-tight">Sedang Berjalan</p>
                    </div>
                    <div class="bg-navy-800/80 border border-navy-700 rounded-xl p-4 text-center min-w-[90px]">
                        <p class="text-2xl font-extrabold text-emerald-400">
                            {{ $selesai }}
                        </p>
                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mt-1 leading-tight">Selesai</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             BILAH PENCARIAN & FILTER — Sesuai design.md §3.2 & screen_06.jpg
        ============================================================ --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5">
            <form method="GET" action="{{ route('riwayat-pengaduan.index') }}"
                  class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

                {{-- Input Pencarian --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="cari"
                           value="{{ request('cari') }}"
                           placeholder="Cari nomor tiket, posisi, atau nama perusahaan..."
                           class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg
                                  focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-200
                                  placeholder:text-gray-400 text-navy-900 bg-gray-50 transition">
                </div>

                {{-- Filter Status --}}
                <select name="status"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-2.5 text-navy-900 bg-gray-50
                               focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-200 transition">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\StatusKasus::cases() as $status)
                        <option value="{{ $status->value }}"
                                {{ request('status') === $status->value ? 'selected' : '' }}>
                            {{ $status->value }}
                        </option>
                    @endforeach
                </select>

                {{-- Tombol Cari --}}
                <button type="submit"
                        class="px-5 py-2.5 bg-navy-900 hover:bg-navy-800 text-white text-sm font-semibold
                               rounded-lg transition shrink-0">
                    Cari
                </button>

                {{-- Reset filter jika ada --}}
                @if(request('cari') || request('status'))
                    <a href="{{ route('riwayat-pengaduan.index') }}"
                       class="px-4 py-2.5 border border-gray-200 hover:border-gray-300 text-gray-600
                              hover:text-gray-800 text-sm rounded-lg transition shrink-0">
                        Reset
                    </a>
                @endif

                <div class="hidden sm:block w-px h-8 bg-gray-200 self-center"></div>

                {{-- Tombol Ajukan Baru --}}
                <a href="{{ route('periksa-lowongan.index') }}"
                   class="px-5 py-2.5 bg-teal-600 hover:bg-teal-500 text-white text-sm font-semibold
                          rounded-lg transition shrink-0 flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Ajukan Pengaduan Baru</span>
                </a>
            </form>
        </div>

        {{-- ============================================================
             DAFTAR PENGADUAN — Daftar kartu responsif / kondisi kosong
        ============================================================ --}}
        <div>
            {{-- Label jumlah hasil --}}
            @if(request('cari') || request('status'))
                <p class="text-xs text-gray-500 mb-3 px-1">
                    Menampilkan <strong class="text-navy-900">{{ $pengaduan->total() }}</strong>
                    hasil
                    @if(request('cari')) untuk "<strong class="text-navy-900">{{ request('cari') }}</strong>"@endif
                    @if(request('status')) dengan status <strong class="text-navy-900">{{ request('status') }}</strong>@endif
                </p>
            @else
                <p class="text-xs text-gray-500 mb-3 px-1">
                    Menampilkan semua pengaduan · <strong class="text-navy-900">{{ $pengaduan->total() }}</strong> total laporan
                </p>
            @endif

            {{-- ======================================================
                 KONDISI KOSONG — Teks Wajib SRS: "Belum ada pengaduan yang diajukan"
                 (LokerLensCopy::BELUM_ADA_PENGADUAN)
            ====================================================== --}}
            @if($pengaduan->isEmpty())
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 sm:p-16 text-center">
                    {{-- Ikon dokumen kosong --}}
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-200 flex items-center
                                justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293
                                     l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>

                    @if(request('cari') || request('status'))
                        <h2 class="text-base font-bold text-navy-900">Data Lowongan Tidak Ditemukan</h2>
                        <p class="text-sm text-gray-500 mt-1 mb-6">
                            Tidak ada pengaduan yang sesuai dengan kata kunci atau filter yang dipilih.
                        </p>
                        <a href="{{ route('riwayat-pengaduan.index') }}"
                           class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200
                                  text-sm text-gray-700 hover:bg-gray-50 font-medium transition">
                            ← Lihat Semua Pengaduan
                        </a>
                    @else
                        {{-- Teks wajib BDD — LokerLensCopy::BELUM_ADA_PENGADUAN --}}
                        <h2 class="text-base font-bold text-navy-900">
                            {{ \App\Constants\LokerLensCopy::BELUM_ADA_PENGADUAN }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1.5 mb-6 max-w-xs mx-auto leading-relaxed">
                            Anda belum memiliki riwayat pengaduan lowongan kerja mencurigakan.
                            Mulai laporan pertama Anda sekarang.
                        </p>
                        <a href="{{ route('periksa-lowongan.index') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-navy-900
                                  hover:bg-navy-800 text-white text-sm font-semibold shadow transition">
                            <span>Ajukan Pengaduan Sekarang</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                    @endif
                </div>

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

                            // Badge warna status kasus
                            $statusColor = match(true) {
                                in_array($status->value ?? $status, [
                                    'Selesai - Risiko Rendah', 'Tidak Terbukti', 'Tidak Ditindaklanjuti'
                                ]) => 'bg-gray-100 text-gray-600 border border-gray-200',
                                in_array($status->value ?? $status, ['Selesai']) => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                in_array($status->value ?? $status, ['Tindak Lanjut Disetujui']) => 'bg-blue-50 text-blue-700 border border-blue-200',
                                in_array($status->value ?? $status, ['Menunggu Keputusan']) => 'bg-amber-50 text-amber-700 border border-amber-200',
                                default => 'bg-teal-50 text-teal-700 border border-teal-200',
                            };

                            // Badge warna skor risiko
                            $scoreColor = match(true) {
                                $score === null                   => 'bg-gray-100 text-gray-500',
                                $score >= 70                      => 'bg-red-50 text-red-700 border border-red-200',
                                $score >= 40                      => 'bg-amber-50 text-amber-700 border border-amber-200',
                                default                           => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                            };
                            $risikoLabel = match(true) {
                                $score === null => '-',
                                $score >= 70    => 'Risiko Tinggi',
                                $score >= 40    => 'Risiko Sedang',
                                default         => 'Risiko Rendah',
                            };
                        @endphp

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200
                                    transition-all duration-200 group">
                            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">

                                {{-- Kolom Kiri: Nomor Tiket & Tanggal --}}
                                <div class="shrink-0 w-full sm:w-40">
                                    <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50
                                                 border border-teal-200 px-2.5 py-1 rounded block w-fit">
                                        {{ $item->nomor_tiket }}
                                    </span>
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
                                    <span class="inline-flex mt-2 px-2.5 py-0.5 rounded text-[11px] font-semibold {{ $statusColor }}">
                                        {{ $status->value ?? $status }}
                                    </span>
                                </div>

                                {{-- Kolom Kanan: Skor & Tombol Lihat Hasil --}}
                                <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 sm:gap-2 shrink-0">
                                    @if($score !== null)
                                        <div class="text-right">
                                            <p class="text-[10px] uppercase tracking-widest font-semibold text-gray-400 leading-none">
                                                KEYAKINAN SISTEM
                                            </p>
                                            <p class="text-2xl font-extrabold text-navy-900 leading-tight mt-0.5">
                                                {{ $score }}<span class="text-sm font-bold text-gray-500">%</span>
                                            </p>
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold {{ $scoreColor }}">
                                                {{ $risikoLabel }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Belum diproses</span>
                                    @endif

                                    <a href="{{ route('periksa-lowongan.hasil', $item->id_pengaduan) }}"
                                       class="mt-auto inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200
                                              hover:border-teal-400 hover:text-teal-700 hover:bg-teal-50 text-xs font-semibold
                                              text-gray-700 transition group-hover:border-teal-300 whitespace-nowrap">
                                        <span>Lihat Hasil</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ======================================================
                     PAGINATION — Laravel Tailwind pagination
                ====================================================== --}}
                @if($pengaduan->hasPages())
                    <div class="mt-6">
                        {{ $pengaduan->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-app-layout>

