@props([
    'daftar',
    'satuan' => '', // mis. "Lowongan Prioritas"
])

{{-- Keterangan jumlah dan tautan halaman di bawah tabel. --}}
<div class="mt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-600 gap-3 border-t border-gray-100 pt-4">
    <div>
        Menampilkan {{ $daftar->firstItem() ?? 0 }} - {{ $daftar->lastItem() ?? 0 }} dari {{ $daftar->total() }} {{ $satuan }}
    </div>
    <div>{{ $daftar->links() }}</div>
</div>
