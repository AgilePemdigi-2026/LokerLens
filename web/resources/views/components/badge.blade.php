@props([
    'nada' => \App\Support\WarnaBadge::ABU,
    'titik' => false,
])

{{-- Bentuk dasar semua badge. Warna (nada) hanya dari App\Support\WarnaBadge. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center w-fit whitespace-nowrap px-2.5 py-0.5 rounded-full text-xs font-semibold ' . \App\Support\WarnaBadge::badge($nada)]) }}>
    @if($titik)
        <span class="w-1.5 h-1.5 rounded-full mr-1.5 shrink-0 bg-current" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
