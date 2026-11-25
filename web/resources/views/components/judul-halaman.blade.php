@props([
    'label' => null,      // teks kecil di atas judul
    'judul' => null,      // bisa juga lewat <x-slot:judul> jika ada kata bercetak miring
    'deskripsi' => null,
])

{{-- Judul halaman (banner navy) yang sama untuk semua peran. Slot utama = isi sebelah kanan. --}}
<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-lg']) }}>
    <div class="absolute -right-8 -bottom-12 w-40 h-40 rounded-full border-[20px] border-teal-400/10 pointer-events-none" aria-hidden="true"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div class="min-w-0">
            @if($label)
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-2">{{ $label }}</p>
            @endif
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">{{ $judul }}</h1>
            @if($deskripsi)
                <p class="text-sm text-gray-300 mt-2 max-w-2xl leading-relaxed">{{ $deskripsi }}</p>
            @endif
        </div>
        @if($slot->isNotEmpty())
            <div class="shrink-0">{{ $slot }}</div>
        @endif
    </div>
</div>
