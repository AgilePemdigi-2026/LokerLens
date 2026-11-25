@props([
    'action',
])

{{-- Form pencarian & filter yang sama untuk semua peran (dropdown dan pencarian terkirim otomatis,
     lihat filterOtomatis di resources/js/app.js). <x-slot:tambahan> = tombol lain setelah "Cari". --}}
<form method="GET" action="{{ $action }}" x-data="filterOtomatis" {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:flex-wrap md:items-end gap-3 bg-gray-50 p-4 rounded-card border border-gray-200 text-navy-900']) }}>
    {{ $slot }}
    <x-tombol type="submit" class="w-full md:w-auto">{{ \App\Constants\LokerLensCopy::BTN_CARI }}</x-tombol>
    {{ $tambahan ?? '' }}
</form>
