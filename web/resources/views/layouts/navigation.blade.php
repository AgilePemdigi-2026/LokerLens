<nav class="bg-navy-900 border-b border-navy-800 text-white sticky top-0 z-50 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left Side: Brand Logo & Navigation Links -->
            <div class="flex items-center space-x-8">
                <!-- Logo LokerLens (ikon lensa + wordmark "Loker" tebal + "Lens" aksen teal) -->
                <a href="{{ Auth::user()->isMasyarakat() ? route('beranda') : (Auth::user()->isAnalis() ? route('tinjau-lowongan.index') : route('tindak-lanjut.index')) }}" class="flex items-center space-x-2 group">
                    <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white shadow-inner group-hover:bg-teal-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">
                        Loker<span class="text-teal-400 font-extrabold">Lens</span>
                    </span>
                </a>

                <!-- Navigation Links per Role (Bagian 5.2 Panduan) -->
                <div class="hidden md:flex items-center space-x-1">
                    @if(Auth::user()->isMasyarakat())
                        <a href="{{ route('beranda') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('beranda') ? 'text-teal-400 bg-navy-800 font-semibold' : 'text-gray-300 hover:text-white hover:bg-navy-800/60' }}">
                            Beranda
                        </a>
                        <a href="{{ route('periksa-lowongan.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('periksa-lowongan.*') ? 'text-teal-400 bg-navy-800 font-semibold' : 'text-gray-300 hover:text-white hover:bg-navy-800/60' }}">
                            Periksa Lowongan
                        </a>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Status Pengaduan
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Riwayat Pengaduan
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                    @elseif(Auth::user()->isAnalis())
                        {{-- Analis Navbar (§5.2): Dashboard · Laporan · Pemantauan (aktif) · Arsip Kasus --}}
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Dashboard
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Laporan
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                        <a href="{{ route('tinjau-lowongan.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('tinjau-lowongan.*') ? 'text-teal-400 bg-navy-800 font-semibold' : 'text-gray-300 hover:text-white hover:bg-navy-800/60' }}">
                            Pemantauan
                        </a>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Arsip Kasus
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                    @elseif(Auth::user()->isKepalaBidang())
                        {{-- Kepala Bidang Navbar (§5.2): Dashboard · Laporan (badge) · Pemantauan · Arsip Kasus --}}
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Dashboard
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                        <a href="{{ route('tindak-lanjut.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition flex items-center gap-2 {{ request()->routeIs('tindak-lanjut.*') || request()->routeIs('buat-surat.*') ? 'text-teal-400 bg-navy-800 font-semibold' : 'text-gray-300 hover:text-white hover:bg-navy-800/60' }}">
                            <span>Laporan</span>
                            @php
                                $badgeCount = \App\Models\Pengaduan::where('status_pengaduan', \App\Enums\StatusKasus::MenungguKeputusan)->count();
                            @endphp
                            @if($badgeCount > 0)
                                <span class="bg-risk-high text-white text-xs px-2 py-0.5 rounded-full font-bold">
                                    {{ $badgeCount }}
                                </span>
                            @endif
                        </a>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Pemantauan
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                        <span class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed flex items-center gap-1.5" title="Fitur Sprint 2">
                            Arsip Kasus
                            <span class="text-[10px] bg-navy-800 border border-gray-700 text-gray-400 px-1.5 py-0.5 rounded">Sprint 2</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Right Side: Notification Icon & User Profile -->
            <div class="flex items-center space-x-4">
                <!-- Notifikasi Bell dengan red dot -->
                <button class="relative p-2 text-gray-300 hover:text-white hover:bg-navy-800 rounded-lg transition" aria-label="Notifikasi">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-risk-high rounded-full ring-2 ring-navy-900"></span>
                </button>

                <!-- Profile Badge & Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-3 p-1.5 rounded-lg hover:bg-navy-800 transition focus:outline-none">
                        <!-- Avatar Inisial -->
                        <div class="w-8 h-8 rounded-full bg-teal-600 text-white font-bold flex items-center justify-center text-sm shadow">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-xs uppercase tracking-wider font-semibold text-teal-400 leading-none">
                                {{ Auth::user()->role }}
                            </div>
                            <div class="text-sm font-medium text-white truncate max-w-[140px]">
                                {{ Auth::user()->name }}
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-48 bg-white text-navy-900 rounded-xl shadow-2xl py-2 border border-gray-100 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 text-xs text-gray-500">
                            Masuk sebagai <strong>{{ Auth::user()->email }}</strong>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-risk-high hover:bg-gray-50 flex items-center space-x-2 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span>Keluar (Log Out)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
