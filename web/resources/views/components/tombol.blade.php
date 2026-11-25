@props([
    'href' => null,        // diisi = tautan <a>, kosong = <button>
    'type' => 'button',
    'varian' => 'utama',
    'ukuran' => 'md',      // sm = di dalam tabel/kartu, md = tombol halaman
])

{{--
    Satu komponen tombol untuk semua peran (bentuk sama, warna sesuai fungsi):
      utama          navy   aksi utama di latar putih (Data Mockup: Periksa Sekarang, Konfirmasi Keputusan)
      aksi           teal   aksi utama di panel gelap, atau memulai langkah berikutnya (mis. Buat Surat)
      sekunder       putih bergaris, untuk kembali/batal/reset
      sekunder-gelap bergaris, untuk panel gelap
      bahaya         merah bergaris, untuk panel gelap
--}}
@php
    $kelasVarian = match ($varian) {
        'aksi'           => 'bg-teal-600 hover:bg-teal-700 border-transparent text-white shadow',
        'sekunder'       => 'bg-white hover:bg-gray-50 border-gray-300 text-navy-900',
        'sekunder-gelap' => 'bg-transparent hover:bg-navy-800 border-gray-500 text-gray-200',
        'bahaya'         => 'bg-transparent border-red-700/50 text-red-300',
        default          => 'bg-navy-900 hover:bg-navy-800 border-transparent text-white shadow',
    };
    $kelasUkuran = $ukuran === 'sm' ? 'px-3 py-1.5' : 'px-5 py-2.5';
    $kelas = "inline-flex items-center justify-center gap-1.5 rounded-control border text-xs font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed {$kelasUkuran} {$kelasVarian}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas . ' whitespace-nowrap']) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
