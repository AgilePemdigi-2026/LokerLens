@props([
    'name' => 'cari',
    'label' => null,
    'placeholder' => '',
    'hapus' => null, // alamat tombol ✕ untuk mengosongkan pencarian
])

{{-- Kolom pencarian di dalam <x-filter>. --}}
<div class="w-full md:w-auto md:flex-[2] md:min-w-[14rem]">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <div class="relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <input type="text" id="{{ $name }}" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
            class="w-full pl-10 {{ $hapus ? 'pr-9' : 'pr-3' }} py-2 rounded-control border border-gray-300 bg-white text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none" />
        @if($hapus && request($name))
            <a href="{{ $hapus }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 hover:text-gray-600">✕</a>
        @endif
    </div>
</div>
