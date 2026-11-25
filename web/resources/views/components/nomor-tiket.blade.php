{{-- Label nomor tiket di kartu. Di tabel, nomor tiket cukup teks tebal tanpa kotak. --}}
<span {{ $attributes->merge(['class' => 'inline-block w-fit font-mono text-xs font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 whitespace-nowrap']) }}>{{ $slot }}</span>
