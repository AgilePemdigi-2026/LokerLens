@props([
    'rapat' => false, // true = jarak kolom lebih sempit (tabel berkolom banyak)
])

{{-- Tabel yang sama untuk semua peran. <x-slot:kepala> berisi <th>, slot utama berisi <tr>. --}}
<div class="overflow-x-auto rounded-card border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm text-navy-900 [&_th]:py-3 [&_td]:py-3.5 {{ $rapat ? '[&_th]:px-3 [&_td]:px-3' : '[&_th]:px-4 [&_td]:px-4' }}">
        <thead class="bg-gray-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
            <tr>{{ $kepala }}</tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            {{ $slot }}
        </tbody>
    </table>
</div>
