<x-app-layout>
    <div class="space-y-6 py-2">
        <div class="bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl">
            <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-1">
                RINGKASAN KASUS
            </p>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                {{ \App\Constants\LokerLensCopy::JUDUL_DASHBOARD_KEPALA_BIDANG }}
            </h1>
            <p class="text-xs sm:text-sm text-gray-300 mt-1">
                {{ \App\Constants\LokerLensCopy::DESKRIPSI_DASHBOARD_KEPALA_BIDANG }}
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($ringkasan as $item)
                <a href="{{ route('tindak-lanjut.index', ['status' => $item['status']->value]) }}"
                   class="bg-white rounded-card shadow-lg p-6 border border-gray-100 hover:shadow-xl transition block">
                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $item['status']->warnaBadge() }}">
                        {{ $item['status']->value }}
                    </span>
                    <p class="text-4xl font-extrabold text-navy-900 mt-4">{{ $item['jumlah'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">kasus</p>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>