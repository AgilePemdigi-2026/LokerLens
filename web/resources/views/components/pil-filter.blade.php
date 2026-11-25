@props([
    'href' => null,
    'aktif' => false,
])

{{-- Pilihan filter berbentuk pil (Status Pengaduan, Arsip Kasus, periode Dashboard Analis). --}}
@php
    $kelas = 'inline-flex items-center px-4 py-1.5 rounded-full text-xs font-semibold border transition '
        . ($aktif ? 'bg-teal-600 border-teal-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:border-teal-600 hover:text-teal-700');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
