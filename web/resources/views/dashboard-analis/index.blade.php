<x-app-layout>
    @php
        $ambang = (int) config('lokerlens.ambang_prioritas');
        $warnaKartu = ['bg-red-500', 'bg-sky-500', 'bg-amber-500', 'bg-teal-500'];

        // Donat distribusi skor
        $segmen = [
            ['kunci' => 'rendah', 'label' => 'Rendah', 'rentang' => '0–49%',                 'warna' => '#94a3b8'],
            ['kunci' => 'sedang', 'label' => 'Sedang', 'rentang' => '50–' . ($ambang - 1) . '%', 'warna' => '#d97706'],
            ['kunci' => 'tinggi', 'label' => 'Tinggi', 'rentang' => $ambang . '–100%',        'warna' => '#dc2626'],
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
        <div class="relative overflow-hidden bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2">PUSAT ANALISIS PENGADUAN</p>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        {{ \App\Constants\LokerLensCopy::JUDUL_DASHBOARD_ANALIS }}
                    </h1>
                    <p class="text-sm text-gray-300 mt-2 max-w-2xl">
                        {{ \App\Constants\LokerLensCopy::DESKRIPSI_DASHBOARD_ANALIS }}
                    </p>
                </div>
                <div class="rounded-lg bg-navy-800/80 border border-navy-700/80 px-4 py-3 shrink-0">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400">Terakhir diperbarui</p>
                    <p class="text-sm font-semibold">{{ $terakhirDiperbarui->locale('id')->translatedFormat('d M Y, H.i') }} WIB</p>
                </div>
            </div>
            <div class="absolute -right-8 -bottom-12 w-40 h-40 rounded-full border-[20px] border-teal-400/10"></div>
            <div class="absolute -right-2 -bottom-6 w-24 h-24 rounded-full border-[12px] border-teal-400/10"></div>
        </div>

        {{-- Filter periode --}}
        <div x-data="{ kustom: {{ $periode === 'kustom' ? 'true' : 'false' }} }"
             class="bg-white rounded-card border border-gray-100 shadow-sm p-4 sm:p-5 text-navy-900">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400">Periode laporan</p>
                    <p class="text-sm font-bold">
                        {{ $dari->locale('id')->translatedFormat('d M Y') }} – {{ $sampai->locale('id')->translatedFormat('d M Y') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="inline-flex rounded-lg bg-gray-100 p-1 text-sm font-medium">
                        <a href="{{ route('dashboard-analis.index', ['periode' => 7]) }}"
                           class="px-4 py-1.5 rounded-md transition {{ $periode === '7' ? 'bg-white shadow text-navy-900 font-semibold' : 'text-gray-500 hover:text-navy-900' }}">7 hari</a>
                        <a href="{{ route('dashboard-analis.index', ['periode' => 30]) }}"
                           class="px-4 py-1.5 rounded-md transition {{ $periode === '30' ? 'bg-white shadow text-navy-900 font-semibold' : 'text-gray-500 hover:text-navy-900' }}">30 hari</a>
                        <button type="button" @click="kustom = true"
                                class="px-4 py-1.5 rounded-md transition {{ $periode === 'kustom' ? 'bg-white shadow text-navy-900 font-semibold' : 'text-gray-500 hover:text-navy-900' }}">Kustom</button>
                    </div>

                    <form method="GET" action="{{ route('dashboard-analis.index') }}" x-show="kustom" x-cloak
                          class="flex flex-wrap items-center gap-2 text-sm">
                        <input type="hidden" name="periode" value="kustom">
                        <input type="date" name="dari" required max="{{ now()->format('Y-m-d') }}"
                               value="{{ $periode === 'kustom' ? $dari->format('Y-m-d') : '' }}"
                               class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <span class="text-gray-400">sampai</span>
                        <input type="date" name="sampai" required max="{{ now()->format('Y-m-d') }}"
                               value="{{ $periode === 'kustom' ? $sampai->format('Y-m-d') : '' }}"
                               class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white font-semibold transition">Terapkan</button>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($ringkasan as $i => $kartu)
                    <div class="bg-white rounded-card border border-gray-100 shadow-sm overflow-hidden">
                        <div class="h-1 {{ $warnaKartu[$i] }}"></div>
                        <div class="p-6">
                            <p class="text-4xl font-extrabold text-navy-900">{{ str_pad($kartu['jumlah'], 2, '0', STR_PAD_LEFT) }}</p>
                            <p class="text-sm font-semibold text-navy-900 mt-2">{{ $kartu['label'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Pada periode terpilih</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 02 Distribusi Skor + 03 Tren --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- 02 Distribusi Skor Risiko --}}
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 text-navy-900">
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
            <div class="bg-white rounded-card border border-gray-100 shadow-sm p-6 text-navy-900">
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
                <div class="bg-white rounded-card border border-gray-100 shadow-sm overflow-hidden text-navy-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="text-left font-semibold px-6 py-3">Lowongan</th>
                                    <th class="text-left font-semibold px-4 py-3">Skor risiko</th>
                                    <th class="text-left font-semibold px-4 py-3">Status</th>
                                    <th class="text-left font-semibold px-4 py-3">Waktu masuk</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($antrean as $hasil)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <p class="text-[11px] font-mono text-teal-700">{{ $hasil->pengaduan->nomor_tiket }}</p>
                                            <p class="font-semibold">{{ $hasil->pengaduan->lowonganKerja->posisi_lowongan ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">{{ $hasil->pengaduan->lowonganKerja->perusahaan->nama_perusahaan ?? '-' }}</p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="text-lg font-extrabold">{{ number_format($hasil->confidence_score, 0) }}</span><span class="text-xs text-gray-500">%</span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $hasil->pengaduan->status_pengaduan->warnaBadge() }}">
                                                {{ $hasil->pengaduan->status_pengaduan->value }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-xs text-gray-500">
                                            {{ $hasil->pengaduan->tanggal_pengaduan?->locale('id')->diffForHumans() }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('tinjau-lowongan.detail', $hasil->id_klasifikasi) }}"
                                               class="inline-block px-3 py-1.5 rounded-lg bg-navy-900 hover:bg-navy-800 text-white text-xs font-semibold transition">
                                                {{ \App\Constants\LokerLensCopy::BTN_LIHAT_DETAIL }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between px-6 py-3 bg-gray-50 text-xs text-gray-500">
                        <span>Menampilkan {{ $antrean->count() }} dari {{ $totalAntrean }} pengaduan</span>
                        <a href="{{ route('tinjau-lowongan.index') }}" class="font-semibold text-teal-700 hover:text-teal-900 transition-colors">Lihat seluruh antrean</a>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-card border border-gray-100 shadow-sm text-navy-900 text-center px-6 py-14">
                    <h3 class="text-xl font-bold">{{ \App\Constants\LokerLensCopy::ANTREAN_ANALIS_KOSONG_JUDUL }}</h3>
                    <p class="text-sm text-gray-500 mt-2">{{ \App\Constants\LokerLensCopy::ANTREAN_ANALIS_KOSONG_KETERANGAN }}</p>
                    @if($periode !== '7')
                        <a href="{{ route('dashboard-analis.index', ['periode' => 7]) }}"
                           class="inline-block mt-5 px-4 py-2 rounded-lg border border-navy-900 text-navy-900 text-sm font-semibold hover:bg-gray-50 transition">
                            {{ \App\Constants\LokerLensCopy::BTN_TAMPILKAN_7_HARI }}
                        </a>
                    @endif
                </div>
            @endif
        </div>

    </div>
</x-app-layout>