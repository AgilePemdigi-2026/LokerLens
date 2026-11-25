@props([
    'status' => null, // App\Enums\StatusKasus atau teks status
])

{{-- Badge status kasus untuk semua peran. Slot (opsional) mengganti teks, warna tetap mengikuti status. --}}
@php
    $teksStatus = $status instanceof \App\Enums\StatusKasus ? $status->value : (string) $status;
@endphp

<x-badge :nada="\App\Support\WarnaBadge::status($status)" :titik="true" {{ $attributes }}>
    {{ $slot->isEmpty() ? $teksStatus : $slot }}
</x-badge>
