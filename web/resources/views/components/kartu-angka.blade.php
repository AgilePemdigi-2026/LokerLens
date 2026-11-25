@props([
    'label',
    'angka',
    'nada' => null,       // warna dari App\Support\WarnaBadge, kosong = netral (teal)
    'keterangan' => null,
    'href' => null,       // diisi = seluruh kartu bisa diklik
])

{{-- Kartu ringkasan angka yang sama untuk semua peran. Atribut (id, data-testid) menempel di angkanya. --}}
@php
    $elemen = $href ? 'a' : 'div';
@endphp

<{{ $elemen }} @if($href) href="{{ $href }}" @endif class="block bg-white rounded-card border border-gray-100 shadow-lg overflow-hidden text-navy-900 {{ $href ? 'transition hover:shadow-xl hover:-translate-y-0.5' : '' }}">
    <div class="h-1 {{ $nada ? \App\Support\WarnaBadge::titik($nada) : 'bg-teal-600' }}"></div>
    <div class="p-5">
        <p class="text-xs font-semibold text-gray-600">{{ $label }}</p>
        <p {{ $attributes->merge(['class' => 'text-3xl font-extrabold text-navy-900 mt-1']) }}>{{ $angka }}</p>
        @if($keterangan)
            <p class="text-xs text-gray-500 mt-1">{{ $keterangan }}</p>
        @endif
        {{ $slot }}
    </div>
</{{ $elemen }}>
