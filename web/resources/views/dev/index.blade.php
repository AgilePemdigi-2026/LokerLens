<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mode Preview — LokerLens</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-cream-50 text-darktext min-h-screen">
    {{-- Mode preview pengembangan: hanya aktif jika APP_ENV=local dan DEV_PREVIEW=true --}}
    <header class="bg-navy-900 text-white">
        <div class="max-w-5xl mx-auto px-4 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400">Khusus Pengembangan</p>
                <h1 class="text-2xl font-extrabold tracking-tight">Loker<span class="text-teal-400">Lens</span> — Mode Preview</h1>
            </div>
            <div class="text-xs text-gray-300 text-left sm:text-right">
                @auth
                    Sedang masuk sebagai <strong class="text-white">{{ Auth::user()->name }}</strong>
                    ({{ Auth::user()->role }})
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="ml-2 underline text-teal-300 hover:text-white">Keluar</button>
                    </form>
                @else
                    Belum masuk
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <div class="bg-amber-50 border-l-4 border-risk-mid p-4 rounded-r-control text-xs sm:text-sm text-amber-900">
            Halaman ini hanya muncul jika <code>APP_ENV=local</code> dan <code>DEV_PREVIEW=true</code>. Aturan login dan hak akses tetap berlaku:
            halaman yang dibuka dengan peran yang salah akan ditolak (403).
        </div>

        @if(session('success'))
            <div class="bg-teal-700 text-white px-4 py-3 rounded-card text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-risk-high text-white px-4 py-3 rounded-card text-sm font-medium">{{ session('error') }}</div>
        @endif

        {{-- Masuk cepat dengan akun demo seeder --}}
        <section class="bg-white rounded-card shadow p-6 border border-gray-100">
            <h2 class="text-lg font-bold text-navy-900 mb-1">Masuk Cepat</h2>
            <p class="text-xs text-mutedtext mb-4">Memakai akun demo dari <code>database/seeders/LokerLensSeeder.php</code>, lalu kembali ke halaman ini.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach($akunDemo as $peran => $email)
                    <form method="POST" action="{{ route('dev.masuk', $peran) }}">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-3 rounded-control text-sm font-semibold transition {{ Auth::user()?->role === $peran ? 'bg-teal-600 text-white' : 'bg-navy-900 hover:bg-navy-800 text-white' }}">
                            Masuk sebagai {{ $peran }}
                        </button>
                        <p class="text-[11px] text-mutedtext mt-1 text-center font-mono">{{ $email }}</p>
                    </form>
                @endforeach
            </div>
        </section>

        {{-- Daftar halaman per peran --}}
        @foreach($tautan as $kelompok => $daftar)
            <section class="bg-white rounded-card shadow p-6 border border-gray-100">
                <h2 class="text-lg font-bold text-navy-900 border-b border-gray-100 pb-2 mb-3">{{ $kelompok }}</h2>
                <ul class="divide-y divide-gray-100">
                    @foreach($daftar as $item)
                        <li class="py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <div>
                                @if($item['url'])
                                    <a href="{{ $item['url'] }}" class="text-sm font-medium text-teal-700 hover:underline">{{ $item['label'] }}</a>
                                    <span class="block text-[11px] text-mutedtext font-mono">{{ parse_url($item['url'], PHP_URL_PATH) }}</span>
                                @else
                                    <span class="text-sm text-gray-400">{{ $item['label'] }}</span>
                                    <span class="block text-[11px] text-risk-mid">Belum ada data. Jalankan <code>php artisan migrate:fresh --seed</code>.</span>
                                @endif
                                @if(! empty($item['catatan']))
                                    <span class="block text-[11px] text-risk-mid">{{ $item['catatan'] }}</span>
                                @endif
                            </div>
                            <span class="shrink-0 self-start sm:self-auto px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-cream-100 text-navy-800 border border-gray-200">
                                Perlu peran: {{ $item['peran'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </main>
</body>
</html>
