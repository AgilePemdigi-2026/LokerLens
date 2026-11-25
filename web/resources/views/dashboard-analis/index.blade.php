<x-app-layout>
    @php
        $ambang = (int) config('lokerlens.ambang_prioritas');

        // Donat distribusi skor (warna risk-low / risk-mid / risk-high di tailwind.config.js)
        $segmen = [
            ['kunci' => 'rendah', 'label' => 'Rendah', 'rentang' => '0–49%',                 'warna' => '#1e8449'],
            ['kunci' => 'sedang', 'label' => 'Sedang', 'rentang' => '50–' . ($ambang - 1) . '%', 'warna' => '#d68910'],
            ['kunci' => 'tinggi', 'label' => 'Tinggi', 'rentang' => $ambang . '–100%',        'warna' => '#c0392b'],
        ];
        $geser = 25; // mulai dari jam 12

        // Grafik garis tren
        $jumlahHari = $tren->count();
        $maksTren = max(1, (int) $tren->max('jumlah'));
        $totalTren = (int) $tren->sum('jumlah');
        $lebar = 600; $tinggi = 160; $bantalan = 12;
        $titik = $tren->values()->map(function ($hari, $i) use ($jumlahHari, $maksTren, $lebar, $tinggi, $bantalan) {
            $x = $jumlahHari > 1 ? round($i / ($jumlahHari - 1) * $lebar, 1) : $lebar / 2;
            $y = round($tinggi - $bantalan - ($hari['jumlah'] / $maksTren) * ($tinggi - 2 * $bantalan), 1);
            return $x . ',' . $y;
        });
        $garis = $titik->implode(' ');
        $luas = '0,' . $tinggi . ' ' . $garis . ' ' . $lebar . ',' . $tinggi;
    @endphp

    <div class="space-y-8 py-2">

        {{-- Header --}}
        <x-judul-halaman label="PUSAT ANALISIS PENGADUAN" :judul="\App\Constants\LokerLensCopy::JUDUL_DASHBOARD_ANALIS" :deskripsi="\App\Constants\LokerLensCopy::DESKRIPSI_DASHBOARD_ANALIS">
            <div class="rounded-control bg-navy-800/80 border border-navy-800 px-4 py-3">
                <p class="text-[10px] uppercase tracking-widest text-gray-400">Terakhir diperbarui</p>
                <p class="text-sm font-semibold">{{ $terakhirDiperbarui->locale('id')->translatedFormat('d M Y, H.i') }} WIB</p>
            </div>
        </x-judul-halaman>

        {{-- Filter periode --}}
        <div x-data="{ kustom: {{ $periode === 'kustom' || $pesanPeriode ? 'true' : 'false' }} }"
             class="bg-white rounded-card border border-gray-100 shadow-lg p-4 sm:p-5 text-navy-900">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400">Periode laporan</p>
                    <p class="text-sm font-bold">
                        {{ $dari->locale('id')->translatedFormat('d M Y') }} – {{ $sampai->locale('id')->translatedFormat('d M Y') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex flex-wrap gap-2">
                        <x-pil-filter :href="route('dashboard-analis.index', ['periode' => 7])" :aktif="$periode === '7'">7 hari</x-pil-filter>
                        <x-pil-filter :href="route('dashboard-analis.index', ['periode' => 30])" :aktif="$periode === '30'">30 hari</x-pil-filter>
                        <x-pil-filter @click="kustom = true" :aktif="$periode === 'kustom'">Kustom</x-pil-filter>
                    </div>

                    {{-- novalidate: tanggal kosong/tidak valid dicek server, pesannya tampil di bawah isian tanggal --}}
                    <form method="GET" action="{{ route('dashboard-analis.index') }}" x-show="kustom" x-cloak novalidate
                          class="flex flex-wrap items-center gap-2 text-sm">
                        <input type="hidden" name="periode" value="kustom">
                        <input type="date" name="dari" required max="{{ now()->format('Y-m-d') }}"
                               value="{{ $periode === 'kustom' ? $dari->format('Y-m-d') : '' }}"
                               class="px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <span class="text-gray-400">sampai</span>
                        <input type="date" name="sampai" required max="{{ now()->format('Y-m-d') }}"
                               value="{{ $periode === 'kustom' ? $sampai->format('Y-m-d') : '' }}"
                               class="px-3 py-2 rounded-control border border-gray-300 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <x-tombol type="submit">Terapkan</x-tombol>
                        @if ($pesanPeriode)
                            <p class="w-full text-xs text-risk-high">{{ $pesanPeriode }}</p>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- 01 Ringkasan Tinjauan --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white">
                    <span class="text-xs text-teal-400 mr-2">01</span>Ringkasan Tinjauan
                </h2>
                <a href="{{ request()->fullUrl() }}" class="text-xs font-semibold text-teal-400 hover:text-teal-300 transition-colors">Perbarui data</a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($ringkasan as $kartu)
                    {{-- Garis atas kartu mengikuti warna status; "Selesai Ditinjau" bukan status (netral) --}}
                    @php $statusKartu = \App\Enums\StatusKasus::tryFrom($kartu['label']); @endphp
                    <x-kartu-angka :label="$kartu['label']" :angka="str_pad($kartu['jumlah'], 2, '0', STR_PAD_LEFT)"
                        :nada="$statusKartu ? \App\Support\WarnaBadge::status($statusKartu) : null" keterangan="Pada periode terpilih" />
                @endforeach
            </div>
        </div>

        {{-- 02 Distribusi Skor + 03 Tren --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- 02 Distribusi Skor Risiko --}}
            <div class="bg-white rounded-card border border-gray-100 shadow-lg p-6 text-navy-900">
                <h2 class="text-lg font-bold pb-3 border-b border-gray-100">
                    <span class="text-xs text-teal-700 mr-2">02</span>Distribusi Skor Risiko
                </h2>

                @if($totalSkor > 0)
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-8 py-6">
                        <div class="relative" style="width:160px;height:160px">
                            <svg viewBox="0 0 42 42" style="width:160px;height:160px" role="img" aria-label="Distribusi skor risiko">
                                <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="5"></circle>
                                @foreach($segmen as $s)
                                    @php
                                        $persen = $distribusi[$s['kunci']] / $totalSkor * 100;
                                    @endphp
                                    @if($persen > 0)
                                        <circle cx="21" cy="21" r="15.9155" fill="none" stroke="{{ $s['warna'] }}" stroke-width="5"
                                                stroke-dasharray="{{ round($persen, 2) }} {{ round(100 - $persen, 2) }}"
                                                stroke-dashoffset="{{ round($geser, 2) }}"></circle>
                                        @php $geser -= $persen; @endphp
                                    @endif
                                @endforeach
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-extrabold">{{ $totalSkor }}</span>
                                <span class="text-xs text-gray-500">pengaduan</span>
                            </div>
                        </div>

                        <ul class="space-y-3 text-sm">
                            @foreach($segmen as $s)
                                <li class="flex items-center gap-3">
                                    <span class="inline-block w-3 h-3 rounded-full" style="background: {{ $s['warna'] }}"></span>
                                    <span class="font-bold w-6 text-right">{{ $distribusi[$s['kunci']] }}</span>
                                    <span class="text-gray-500">{{ $s['label'] }} · {{ $s['rentang'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-sm text-gray-500 text-center py-16">Belum ada data skor</p>
                @endif
            </div>

            {{-- 03 Tren Pengaduan --}}
            <div class="bg-white rounded-card border border-gray-100 shadow-lg p-6 text-navy-900">
                <h2 class="text-lg font-bold pb-3 border-b border-gray-100">
                    <span class="text-xs text-teal-700 mr-2">03</span>Tren Pengaduan
                </h2>

                @if($totalTren > 0)
                    <div class="flex items-center justify-between py-3 text-xs text-gray-500">
                        <span class="flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-teal-600"></span>Pengaduan masuk per hari
                        </span>
                        <span><strong class="text-xl text-navy-900">{{ $totalTren }}</strong> total</span>
                    </div>

                                        @if($jumlahHari > 1)
                        <svg viewBox="0 0 {{ $lebar }} {{ $tinggi }}" preserveAspectRatio="none" style="width:100%;height:160px" role="img" aria-label="Grafik tren pengaduan">
                            <polygon points="{{ $luas }}" fill="#0d9488" fill-opacity="0.12"></polygon>
                            <polyline points="{{ $garis }}" fill="none" stroke="#0d9488" stroke-width="2.5"
                                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
                        </svg>

                        <div class="flex justify-between text-[11px] text-gray-400 mt-2">
                            <span>{{ $tren->first()['tanggal']->locale('id')->translatedFormat('d M') }}</span>
                            @if($jumlahHari > 2)
                                <span>{{ $tren[intdiv($jumlahHari, 2)]['tanggal']->locale('id')->translatedFormat('d M') }}</span>
                            @endif
                            <span>{{ $tren->last()['tanggal']->locale('id')->translatedFormat('d M') }}</span>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-12">
                            Periode satu hari ({{ $tren->first()['tanggal']->locale('id')->translatedFormat('d M Y') }}). Grafik tren butuh minimal dua hari.
                        </p>
                    @endif
                @else
                    <p class="text-sm text-gray-500 text-center py-16">Belum ada aktivitas</p>
                @endif
            </div>
        </div>

        {{-- 04 Antrean Prioritas Tinggi --}}
        <div>
            <div class="mb-4">
                <h2 class="text-lg font-bold text-white">
                    <span class="text-xs text-teal-400 mr-2">04</span>Antrean Prioritas Tinggi
                </h2>
                <p class="text-xs text-gray-400 mt-1">Urutan berdasarkan skor tertinggi.</p>
            </div>

            @if($antrean->isNotEmpty())
                <div class="bg-white rounded-card border border-gray-100 shadow-lg p-6 text-navy-900">
                    <x-tabel>
                        <x-slot:kepala>
                            <th>Lowongan</th>
                            <th>Skor risiko</th>
                            <th>Status</th>
                            <th class="whitespace-nowrap">Waktu masuk</th>
                            <th></th>
                        </x-slot:kepala>
                        @foreach($antrean as $hasil)
                            <tr class="hover:bg-gray-50 transition">
                                <td>
                                    <p class="font-mono font-bold text-teal-800 whitespace-nowrap">{{ $hasil->pengaduan->nomor_tiket }}</p>
                                    <p class="font-semibold">{{ $hasil->pengaduan->lowonganKerja->posisi_lowongan ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $hasil->pengaduan->lowonganKerja->perusahaan->nama_perusahaan ?? '-' }}</p>
                                </td>
                                <td><x-badge-skor :klasifikasi="$hasil" /></td>
                                <td><x-badge-status :status="$hasil->pengaduan->status_pengaduan" /></td>
                                <td class="text-gray-600 whitespace-nowrap">
                                    {{ $hasil->pengaduan->tanggal_pengaduan?->locale('id')->diffForHumans() }}
                                </td>
                                <td class="text-right">
                                    <x-tombol ukuran="sm" :href="route('tinjau-lowongan.detail', $hasil->id_klasifikasi)">{{ \App\Constants\LokerLensCopy::BTN_LIHAT_DETAIL }}</x-tombol>
                                </td>
                            </tr>
                        @endforeach
                    </x-tabel>
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-4 text-xs text-gray-600">
                        <span>Menampilkan {{ $antrean->count() }} dari {{ $totalAntrean }} pengaduan</span>
                        <a href="{{ route('tinjau-lowongan.index') }}" class="font-semibold text-teal-700 hover:text-teal-900 transition-colors">Lihat seluruh antrean</a>
                    </div>
                </div>
            @else
                <x-pesan-kosong :judul="\App\Constants\LokerLensCopy::ANTREAN_ANALIS_KOSONG_JUDUL" :keterangan="\App\Constants\LokerLensCopy::ANTREAN_ANALIS_KOSONG_KETERANGAN">
                    @if($periode !== '7')
                        <x-tombol varian="sekunder" :href="route('dashboard-analis.index', ['periode' => 7])">{{ \App\Constants\LokerLensCopy::BTN_TAMPILKAN_7_HARI }}</x-tombol>
                    @endif
                </x-pesan-kosong>
            @endif
        </div>

    </div>
</x-app-layout>