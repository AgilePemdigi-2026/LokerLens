@props([
    'kasus' => [],
    'peran' => null, // 'analis' atau 'kepala-bidang', otomatis mendeteksi peran user jika kosong
    'tampilkanPaginasi' => true,
])

@php
    use App\Enums\StatusKasus;

    // Tentukan peran pengguna jika tidak dispesifikasikan
    $peranAktif = $peran ?? (auth()->user()?->role === 'Kepala Bidang' ? 'kepala-bidang' : 'analis');
    $isKepalaBidang = $peranAktif === 'kepala-bidang';
    $ambang = (float) config('lokerlens.ambang_prioritas', 70);
@endphp

<div class="space-y-4">
    <!-- Kontainer Tabel Responsif -->
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-left text-xs sm:text-sm">
            <thead class="bg-gray-100 text-gray-700 font-bold uppercase tracking-wider text-[11px]">
                <tr>
                    <th scope="col" class="px-4 py-3">{{ $isKepalaBidang ? 'ID LAPORAN' : 'ID LOWONGAN' }}</th>
                    <th scope="col" class="px-4 py-3">NAMA PERUSAHAAN</th>
                    <th scope="col" class="px-4 py-3">POSISI DITAWARKAN</th>
                    <th scope="col" class="px-4 py-3">{{ $isKepalaBidang ? 'SKOR KECURIGAAN' : 'CONFIDENCE SCORE' }}</th>
                    <th scope="col" class="px-4 py-3">{{ $isKepalaBidang ? 'STATUS KASUS' : 'STATUS SISTEM' }}</th>
                    <th scope="col" class="px-4 py-3 text-right">AKSI</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($kasus as $item)
                    @php
                        // Normalisasi model apakah berupa HasilKlasifikasi atau Pengaduan
                        if ($item instanceof \App\Models\HasilKlasifikasi) {
                            $klasifikasi = $item;
                            $pengaduan = $item->pengaduan;
                        } elseif ($item instanceof \App\Models\Pengaduan) {
                            $pengaduan = $item;
                            $klasifikasi = $item->hasilKlasifikasi;
                        } else {
                            $pengaduan = $item->pengaduan ?? $item;
                            $klasifikasi = $item->hasilKlasifikasi ?? ($item instanceof \App\Models\HasilKlasifikasi ? $item : null);
                        }

                        $lowongan = $pengaduan?->lowonganKerja;
                        $perusahaan = $lowongan?->perusahaan;
                        $score = $klasifikasi ? (float) $klasifikasi->confidence_score : 0;
                        $status = $pengaduan?->status_pengaduan;
                        $statusValue = $status instanceof StatusKasus ? $status->value : (string) $status;

                        // Penentuan tautan detail berdasarkan peran
                        if ($isKepalaBidang) {
                            $detailUrl = route('tindak-lanjut.detail', $pengaduan?->id_pengaduan ?? $item->id);
                        } else {
                            $detailUrl = route('tinjau-lowongan.detail', $klasifikasi?->id_klasifikasi ?? $item->id);
                        }

                        $isSedangDitinjau = $statusValue === StatusKasus::SedangDitinjau->value;
                        $isTindakLanjutDisetujui = $statusValue === StatusKasus::TindakLanjutDisetujui->value;
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition">
                        <!-- ID Lowongan / Tiket -->
                        <td class="px-4 py-3.5 font-mono font-bold text-teal-800 whitespace-nowrap">
                            {{ $pengaduan?->nomor_tiket ?? '-' }}
                            @if($isKepalaBidang && $pengaduan?->tanggal_pengaduan)
                                <div class="text-[10px] text-gray-400 font-sans font-normal mt-0.5">
                                    {{ $pengaduan->tanggal_pengaduan->translatedFormat('d M Y') }}
                                </div>
                            @endif
                        </td>

                        <!-- Nama Perusahaan & Status Legalitas -->
                        <td class="px-4 py-3.5">
                            <div class="font-bold text-navy-900">
                                {{ $perusahaan?->nama_perusahaan ?? 'Entitas Tidak Terdaftar' }}
                            </div>
                            <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                <svg class="w-3 h-3 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="truncate max-w-xs">{{ $perusahaan?->status_terdaftar ?? 'Entitas Tak Terdaftar Kemenaker / AHU' }}</span>
                            </div>
                        </td>

                        <!-- Posisi Ditawarkan -->
                        <td class="px-4 py-3.5 font-medium text-gray-900">
                            {{ $lowongan?->posisi_lowongan ?? '-' }}
                        </td>

                        <!-- Confidence Score / Skor Kecurigaan -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $score >= $ambang ? 'bg-red-100 text-risk-high border border-red-200' : 'bg-amber-100 text-risk-mid border border-amber-200' }}">
                                {{ number_format($score, 0) }}% Indikasi Penipuan
                            </span>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                {{ $score >= $ambang ? 'Skor Kritis (Tingkat Bahaya)' : 'Perlu Tinjauan' }}
                            </div>
                        </td>

                        <!-- Status Kasus / Sistem (Komponen badge-status) -->
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-badge-status :status="$status" />
                        </td>

                        <!-- Tombol Aksi -->
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 justify-end">
                                @if(! $isKepalaBidang && $isSedangDitinjau)
                                    <a href="{{ $detailUrl }}" class="inline-flex items-center px-3 py-1.5 rounded-control bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs shadow transition">
                                        Sedang Ditinjau
                                    </a>
                                @else
                                    <a href="{{ $detailUrl }}" class="inline-flex items-center px-3 py-1.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs shadow transition">
                                        Lihat Detail
                                    </a>
                                @endif

                                @if($isKepalaBidang && $isTindakLanjutDisetujui && $pengaduan)
                                    <a href="{{ route('buat-surat.index', $pengaduan->id_pengaduan) }}"
                                       class="inline-flex items-center px-2.5 py-1.5 rounded-control bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow transition"
                                       title="Buat Laporan Polisi">
                                        Surat Polisi →
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <!-- Tampilan Pesan Kosong -->
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="max-w-xs mx-auto">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @if($isKepalaBidang)
                                    <p class="mt-3 text-base font-bold text-navy-900">
                                        Belum Ada Laporan yang Menunggu Keputusan
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Semua laporan dari analis telah diproses atau belum ada eskalasi baru.
                                    </p>
                                @else
                                    <p class="mt-3 text-base font-bold text-navy-900">
                                        Data Lowongan Tidak Ditemukan
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Tidak ada lowongan prioritas tinggi yang cocok dengan kata kunci atau filter yang Anda pilih.
                                    </p>
                                    <a href="{{ route('tinjau-lowongan.index') }}" class="mt-4 inline-block text-xs font-semibold text-teal-600 hover:underline">
                                        Reset Filter Pencarian
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginasi -->
    @if($tampilkanPaginasi && is_object($kasus) && method_exists($kasus, 'links') && method_exists($kasus, 'total') && $kasus->total() > 0)
        <div class="flex flex-col sm:flex-row items-center justify-between text-xs text-gray-600 gap-3 border-t border-gray-100 pt-4">
            <div>
                Menampilkan {{ $kasus->firstItem() ?? 0 }} - {{ $kasus->lastItem() ?? 0 }} dari {{ $kasus->total() }} {{ $isKepalaBidang ? 'Laporan' : 'Lowongan Prioritas' }}
            </div>
            <div>
                {{ $kasus->links() }}
            </div>
        </div>
    @endif
</div>
