@props([
    'tipe' => 'sukses', // sukses, galat, peringatan, atau netral
])

{{-- Pesan di dalam halaman yang sama untuk semua peran. Pesan session('success'/'error') sudah tampil di layouts/app. --}}
@php
    $warna = match ($tipe) {
        'galat'      => 'bg-red-50 border-risk-high text-red-900',
        'peringatan' => 'bg-amber-50 border-risk-mid text-amber-900',
        'netral'     => 'bg-slate-100 border-slate-600 text-slate-800',
        default      => 'bg-emerald-50 border-risk-low text-emerald-900',
    };
@endphp

<div {{ $attributes->merge(['class' => "border-l-4 p-4 rounded-r-control text-xs sm:text-sm leading-relaxed {$warna}"]) }}>
    {{ $slot }}
</div>
