@use('App\Constants\LokerLensCopy')
@use('App\Support\ProgresPengaduan')

{{-- Status Pengaduan — detail satu pengaduan (issue #11).
     Layar besar: kiri info + skor, kanan progres + catatan. HP: bertumpuk. --}}
<x-app-layout>
    @php
        $lowongan = $pengaduan->lowonganKerja;
        $klasifikasi = $pengaduan->hasilKlasifikasi;
        // Kembali ke daftar dengan filter terakhir
        $urlDaftar = route('status-pengaduan.index', $filter === ProgresPengaduan::FILTER_SEMUA ? [] : ['filter' => $filter]);
    @endphp

    <div class="max-w-6xl mx-auto py-4 space-y-6">
        <!-- Breadcrumb & tombol kembali -->
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-400">
            <nav class="flex items-center space-x-2" aria-label="Breadcrumb">
                <a href="{{ route('beranda') }}" class="hover:text-teal-400 transition">Beranda</a>
                <span>/</span>
                <a href="{{ $urlDaftar }}" class="hover:text-teal-400 transition">Status Pengaduan</a>
                <span>/</span>
                <span class="text-white font-medium font-mono">{{ $pengaduan->nomor_tiket }}</span>
            </nav>
            <a href="{{ $urlDaftar }}" id="kembali-ke-daftar" class="text-teal-400 hover:underline font-medium">
                ← {{ LokerLensCopy::BTN_KEMBALI_STATUS }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
            <!-- ═══ Kiri: info pengaduan + skor ═══ -->
            <div class="lg:col-span-2 space-y-6">
                <!-- a. Identitas pengaduan, badge kelompok + status lengkap, penjelasan -->
                <section class="bg-white rounded-card shadow-xl p-6 border border-gray-100 text-navy-900">
                    <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                        {{ $pengaduan->nomor_tiket }}
                    </span>
                    <h1 class="font-serif text-2xl sm:text-3xl font-bold mt-3 leading-snug">{{ $lowongan->posisi_lowongan ?? '-' }}</h1>
                    <p class="text-sm text-gray-600">{{ $lowongan->perusahaan->nama_perusahaan ?? '-' }}</p>

                    <p id="status-pengaduan" class="flex flex-wrap items-center gap-2 mt-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $progres->warnaKelompok }}">{{ $progres->labelKelompok }}</span>
                        <span class="text-gray-400" aria-hidden="true">·</span>
                        <span class="text-sm font-bold">{{ $progres->labelStatus }}</span>
                    </p>
                    <p class="text-sm text-gray-700 mt-2 leading-relaxed">{{ $progres->penjelasan }}</p>
                </section>

                <!-- b. Skor risiko, kategori hasil, dan kata pemicu -->
                <section class="bg-white rounded-card shadow-lg p-6 border border-gray-100 text-navy-900 space-y-5">
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ LokerLensCopy::LABEL_SKOR_DAN_KATEGORI }}</h2>
                        @if($klasifikasi)
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-4xl font-black font-mono {{ $klasifikasi->warna_risiko['teks'] }}">{{ number_format($klasifikasi->confidence_score, 0) }}%</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $klasifikasi->warna_risiko['badge'] }}">{{ $klasifikasi->label_risiko }}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-1">{{ $klasifikasi->kategori_hasil }}</p>
                        @else
                            <p class="text-sm font-semibold text-gray-400 mt-2">{{ LokerLensCopy::SKOR_BELUM_TERSEDIA }}</p>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ LokerLensCopy::LABEL_KATA_PEMICU }}</h2>
                        <div class="flex flex-wrap gap-2 mt-2">
                            {{-- Kata pemicu sudah dirapikan dan tanpa duplikat (HasilKlasifikasi::kata_pemicu_unik) --}}
                            @forelse($klasifikasi?->kata_pemicu_unik ?? [] as $kata)
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $klasifikasi->warna_risiko['badge'] }}">{{ $kata }}</span>
                            @empty
                                <span class="text-sm text-gray-400">{{ $klasifikasi ? LokerLensCopy::TIDAK_ADA_KATA_PEMICU : LokerLensCopy::SKOR_BELUM_TERSEDIA }}</span>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>

            <!-- ═══ Kanan: progres 5 tahap + catatan ═══ -->
            <div class="lg:col-span-3 space-y-6">
                <!-- c. Progres 5 tahap -->
                <section class="bg-white rounded-card shadow-lg p-6 border border-gray-100 text-navy-900">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4">{{ LokerLensCopy::LABEL_PROGRES_PENANGANAN }}</h2>
                    <ol id="timeline-progres">
                        @foreach($progres->tahap as $tahap)
                            @php
                                $keadaan = $tahap['keadaan'];
                                // Gaya ikon, label, dan kotak per keadaan tahap
                                $ikon = match ($keadaan) {
                                    ProgresPengaduan::SELESAI     => 'bg-teal-600 text-white',
                                    ProgresPengaduan::BERLANGSUNG => 'bg-white border-2 border-teal-600 text-teal-700',
                                    ProgresPengaduan::DIHENTIKAN  => 'bg-red-50 border-2 border-risk-high text-risk-high',
                                    default                       => 'bg-gray-100 border border-gray-200 text-gray-400',
                                };
                                $label = match ($keadaan) {
                                    ProgresPengaduan::SELESAI     => 'bg-teal-50 text-teal-700 border border-teal-200',
                                    ProgresPengaduan::BERLANGSUNG => 'bg-teal-600 text-white',
                                    ProgresPengaduan::DIHENTIKAN  => 'bg-red-100 text-risk-high border border-red-200',
                                    default                       => 'bg-gray-100 text-gray-500 border border-gray-200',
                                };
                                $kotak = match ($keadaan) {
                                    ProgresPengaduan::BERLANGSUNG => 'bg-teal-50',
                                    ProgresPengaduan::DIHENTIKAN  => 'bg-red-50',
                                    default                       => '',
                                };
                            @endphp
                            <li class="flex gap-3" data-tahap="{{ $tahap['nomor'] }}" data-keadaan="{{ $keadaan }}">
                                <!-- Ikon + garis penghubung -->
                                <div class="flex flex-col items-center">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $ikon }}">
                                        @if($keadaan === ProgresPengaduan::SELESAI)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @elseif($keadaan === ProgresPengaduan::DIHENTIKAN)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        @else
                                            {{ $tahap['nomor'] }}
                                        @endif
                                    </span>
                                    @unless($loop->last)
                                        <span class="w-0.5 flex-1 min-h-5 {{ $keadaan === ProgresPengaduan::SELESAI ? 'bg-teal-600' : 'bg-gray-200' }}"></span>
                                    @endunless
                                </div>

                                <!-- Judul, keterangan, tanggal, label keadaan -->
                                <div class="flex-1 rounded-control px-3 py-2 {{ $loop->last ? '' : 'mb-2' }} {{ $kotak }}">
                                    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                                        <p class="text-sm font-semibold {{ $keadaan === ProgresPengaduan::BELUM ? 'text-gray-400' : 'text-navy-900' }}">
                                            <span class="sr-only">Tahap {{ $tahap['nomor'] }}:</span>{{ $tahap['judul'] }}
                                        </p>
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $label }}">
                                            {{ $tahap['label'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs mt-0.5 {{ $keadaan === ProgresPengaduan::BELUM ? 'text-gray-400' : 'text-gray-600' }}">{{ $tahap['keterangan'] }}</p>
                                    @if($keadaan !== ProgresPengaduan::BELUM)
                                        <p class="text-[11px] text-gray-500 font-mono mt-1">{{ ProgresPengaduan::formatTanggal($tahap['tanggal']) }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if($progres->pesanBerhenti)
                        <!-- Penutup: pengaduan berhenti di tahap ini -->
                        @php $dihentikan = last($progres->tahap)['keadaan'] === ProgresPengaduan::DIHENTIKAN; @endphp
                        <div id="pesan-berhenti" role="status"
                             class="mt-4 border-l-4 p-3 rounded-r-lg text-sm leading-relaxed {{ $dihentikan ? 'bg-red-50 border-risk-high text-red-900' : 'bg-emerald-50 border-risk-low text-emerald-900' }}">
                            {{ $progres->pesanBerhenti }}
                        </div>
                    @endif
                </section>

                <!-- d. Pembaruan terakhir, catatan, dan tombol ke Hasil Pemeriksaan -->
                <section class="bg-white rounded-card shadow-lg p-6 border border-gray-100 text-navy-900 space-y-4">
                    <p class="text-sm flex flex-wrap justify-between gap-2">
                        <span class="text-gray-500">{{ LokerLensCopy::LABEL_PEMBARUAN_TERAKHIR }}</span>
                        <strong>{{ ProgresPengaduan::formatTanggal($progres->pembaruanTerakhir) }}</strong>
                    </p>
                    <p class="bg-gray-50 border border-gray-200 rounded-control p-3 text-xs text-gray-700 leading-relaxed">
                        {{ $progres->ditutup ? LokerLensCopy::CATATAN_PENGADUAN_DITUTUP : LokerLensCopy::CATATAN_TANPA_TINDAKAN }}
                    </p>
                    <p class="text-xs text-gray-500 italic leading-relaxed">
                        * Catatan: {{ LokerLensCopy::CATATAN_PENDUKUNG_KEPUTUSAN }}
                    </p>
                    <a href="{{ route('periksa-lowongan.hasil', $pengaduan->id_pengaduan) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-control bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold shadow transition gap-1.5">
                        <span>{{ LokerLensCopy::BTN_LIHAT_HASIL_PERIKSA }}</span>
                        <span>→</span>
                    </a>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
