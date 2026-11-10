@use('App\Constants\LokerLensCopy')
@php
    $pengguna = Auth::user();
    $terang   = $pengguna->isMasyarakat(); // Masyarakat: navbar putih; Analis & Kepala Bidang: navbar navy

    // Daftar menu per peran (Bagian 5.2 Panduan). Dipakai dua kali: menu desktop dan menu HP,
    // supaya isinya selalu sama. 'aktif' = pola nama route yang menandai menu sedang dibuka.
    if ($pengguna->isMasyarakat()) {
        // Navbar Masyarakat (Gambar 2.4): Ajukan Pengaduan · Status Pengaduan · Riwayat Pengaduan
        $menu = [
            ['label' => LokerLensCopy::BTN_AJUKAN_PENGADUAN, 'url' => route('periksa-lowongan.index'), 'aktif' => ['periksa-lowongan.*']],
            ['label' => LokerLensCopy::NAV_STATUS_PENGADUAN, 'url' => route('status-pengaduan.index'), 'aktif' => ['status-pengaduan.*']],
            ['label' => LokerLensCopy::BTN_RIWAYAT_PENGADUAN, 'url' => route('riwayat-pengaduan.index'), 'aktif' => ['riwayat-pengaduan.*']],
        ];
    } elseif ($pengguna->isAnalis()) {
        // Navbar Analis: Dashboard · Pemantauan · Riwayat (issue #25)
        $menu = [
            ['label' => LokerLensCopy::NAV_DASHBOARD, 'url' => route('dashboard-analis.index'), 'aktif' => ['dashboard-analis.*']],
            ['label' => LokerLensCopy::NAV_PEMANTAUAN, 'url' => route('tinjau-lowongan.index'), 'aktif' => ['tinjau-lowongan.*']],
            ['label' => LokerLensCopy::NAV_RIWAYAT, 'url' => route('riwayat-tinjauan.index'), 'aktif' => ['riwayat-tinjauan.*']],
        ];
    } else {
        // Navbar Kepala Bidang: Dashboard · Laporan (badge jumlah Menunggu Keputusan) · Arsip Kasus
        $menu = [
            ['label' => LokerLensCopy::NAV_DASHBOARD, 'url' => route('dashboard-kepala-bidang.index'), 'aktif' => ['dashboard-kepala-bidang.*']],
            ['label' => LokerLensCopy::NAV_LAPORAN, 'url' => route('tindak-lanjut.index'), 'aktif' => ['tindak-lanjut.*', 'buat-surat.*'],
             'badge' => \App\Models\Pengaduan::where('status_pengaduan', \App\Enums\StatusKasus::MenungguKeputusan)->count()],
            ['label' => LokerLensCopy::NAV_ARSIP_KASUS, 'url' => route('arsip-kasus.index'), 'aktif' => ['arsip-kasus.*']],
        ];
    }

    // Warna menu aktif / biasa, sama seperti sebelumnya
    $kelasAktif = $terang ? 'text-teal-700 bg-teal-50 font-semibold' : 'text-teal-400 bg-navy-800 font-semibold';
    $kelasBiasa = $terang ? 'text-gray-600 hover:text-navy-900 hover:bg-gray-100' : 'text-gray-300 hover:text-white hover:bg-navy-800/60';
@endphp

<nav x-data="{ menuHp: false }" class="{{ $terang ? 'bg-white border-b border-gray-200 shadow-sm' : 'bg-navy-900 border-b border-navy-800 shadow-md' }} sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Kiri: Logo & menu desktop -->
            <div class="flex items-center space-x-8">
                <!-- Logo LokerLens → halaman awal peran (User::halamanAwal) -->
                <a href="{{ $pengguna->halamanAwal() }}" class="flex items-center space-x-2 group">
                    <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white shadow-inner group-hover:bg-teal-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight {{ $terang ? 'text-navy-900' : 'text-white' }}">
                        Loker<span class="text-teal-600 font-extrabold">Lens</span>
                    </span>
                </a>

                <!-- Menu desktop (layar md ke atas). Ditulis sebelum menu HP supaya BDD mengklik tautan ini lebih dulu. -->
                <div class="hidden md:flex items-center space-x-1">
                    @foreach($menu as $item)
                        <a href="{{ $item['url'] }}" class="px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-2 {{ request()->routeIs(...$item['aktif']) ? $kelasAktif : $kelasBiasa }}">
                            <span>{{ $item['label'] }}</span>
                            @if(($item['badge'] ?? 0) > 0)
                                <span class="bg-risk-high text-white text-xs px-2 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Kanan: profil & tombol menu HP. Ikon lonceng dihapus karena fitur notifikasi tidak ada di SRS. -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- Profile Badge & Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-3 p-1.5 rounded-lg {{ $terang ? 'hover:bg-gray-100' : 'hover:bg-navy-800' }} transition focus:outline-none">
                        <!-- Avatar Inisial -->
                        <div class="w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center text-sm shadow">
                            {{ strtoupper(substr($pengguna->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs uppercase tracking-wider font-semibold {{ $terang ? 'text-teal-700' : 'text-teal-400' }} leading-none">
                                {{ $pengguna->role }}
                            </div>
                            <div class="text-sm font-medium {{ $terang ? 'text-navy-900' : 'text-white' }} truncate max-w-[140px]">
                                {{ $pengguna->name }}
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-56 bg-white text-navy-900 rounded-xl shadow-2xl py-2 border border-gray-100 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 text-xs text-gray-500 break-all">
                            Masuk sebagai <strong>{{ $pengguna->email }}</strong>
                        </div>
                        <!-- Profil: ubah nama, email, dan kata sandi -->
                        <a href="{{ route('profile.edit') }}" class="w-full px-4 py-2 text-sm text-navy-900 hover:bg-gray-50 flex items-center space-x-2 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>{{ LokerLensCopy::NAV_PROFIL }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-risk-high hover:bg-gray-50 flex items-center space-x-2 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span>{{ LokerLensCopy::NAV_KELUAR }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tombol hamburger: hanya tampil di layar kecil (di bawah md) -->
                <button type="button" @click="menuHp = !menuHp" id="tombol-menu-hp"
                        class="md:hidden p-2 rounded-lg transition {{ $terang ? 'text-navy-900 hover:bg-gray-100' : 'text-white hover:bg-navy-800' }}"
                        :aria-expanded="menuHp.toString()" aria-controls="menu-hp"
                        :aria-label="menuHp ? @js(LokerLensCopy::NAV_TUTUP_MENU) : @js(LokerLensCopy::NAV_BUKA_MENU)"
                        aria-label="{{ LokerLensCopy::NAV_BUKA_MENU }}">
                    <!-- Ikon garis tiga (tertutup) / silang (terbuka) -->
                    <svg x-show="!menuHp" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg x-show="menuHp" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu HP: isi sama dengan menu desktop, tampil di bawah navbar saat tombol hamburger ditekan -->
    <div id="menu-hp" x-show="menuHp" x-cloak
         class="md:hidden border-t {{ $terang ? 'border-gray-200 bg-white' : 'border-navy-800 bg-navy-900' }}">
        <div class="px-4 py-3 space-y-1">
            @foreach($menu as $item)
                <a href="{{ $item['url'] }}" class="px-3 py-2.5 rounded-md text-sm font-medium transition flex items-center justify-between {{ request()->routeIs(...$item['aktif']) ? $kelasAktif : $kelasBiasa }}">
                    <span>{{ $item['label'] }}</span>
                    @if(($item['badge'] ?? 0) > 0)
                        <span class="bg-risk-high text-white text-xs px-2 py-0.5 rounded-full font-bold">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</nav>
