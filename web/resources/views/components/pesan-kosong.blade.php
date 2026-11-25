@props([
    'judul',
    'keterangan' => null,
    'kartu' => true, // false = tanpa kartu (di dalam tabel atau kartu lain)
])

{{-- Pesan kosong yang sama untuk semua peran. Slot (opsional) berisi tombol. --}}
<div {{ $attributes->merge(['class' => 'px-6 py-12 text-center text-gray-500 ' . ($kartu ? 'bg-white rounded-card shadow-lg border border-gray-100' : '')]) }}>
    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <p class="mt-3 text-base font-bold text-navy-900">{{ $judul }}</p>
    @if($keterangan)
        <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto leading-relaxed">{{ $keterangan }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-3">{{ $slot }}</div>
    @endif
</div>
