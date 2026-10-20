<x-app-layout>
    <div class="max-w-7xl mx-auto py-4 space-y-6">
        <!-- Banner Gelap Pusat Penanganan Laporan (Bagian 6.5.1) -->
        <div class="bg-navy-900 border border-navy-800 rounded-card p-6 sm:p-8 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <p class="text-xs uppercase tracking-widest font-bold text-teal-400 mb-1">
                    PUSAT PENANGANAN LAPORAN
                </p>
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Daftar Laporan
                </h1>
                <p class="text-xs sm:text-sm text-gray-300 mt-1 max-w-2xl leading-relaxed">
                    Tinjau laporan yang telah diperiksa analis dan tetapkan keputusan tindak lanjut kasus.
                </p>
            </div>

            <!-- Kotak Angka Antrean Keputusan -->
            <div class="bg-navy-800/80 border border-navy-700 rounded-xl p-4 sm:p-5 flex items-center space-x-4 shrink-0">
                <div class="text-4xl font-black text-teal-400 font-mono tracking-tight">
                    {{ sprintf('%02d', $jumlahMenunggu) }}
                </div>
                <div>
                    <span class="text-xs font-bold text-white block uppercase tracking-wider">
                        Menunggu Keputusan
                    </span>
                    {{-- §6.5.1: rincian "2 Prioritas tinggi · 1 Prioritas sedang" --}}
                    <span class="text-[11px] text-gray-400">
                        {{ $jumlahPrioritasTinggi }} Prioritas tinggi · {{ $jumlahPrioritasSedang }} Prioritas sedang
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter & Pencarian Laporan (§6.5.1: pencarian, filter Status, filter Prioritas) -->
        <div class="bg-white rounded-card shadow-lg p-5 border border-gray-100">
            <form method="GET" action="{{ route('tindak-lanjut.index') }}" class="flex flex-col sm:flex-row items-end gap-3">
                <div class="relative flex-1 w-full">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Pencarian</label>
                    <div class="relative">
                        <input type="text" name="cari" value="{{ request('cari') }}"
                            placeholder="Cari laporan, posisi, atau perusahaan..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none" />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2.5 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Keputusan" {{ request('status') === 'Menunggu Keputusan' ? 'selected' : '' }}>Menunggu Keputusan</option>
                        <option value="Tindak Lanjut Disetujui" {{ request('status') === 'Tindak Lanjut Disetujui' ? 'selected' : '' }}>Tindak Lanjut Disetujui</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Prioritas</label>
                    <select name="prioritas" class="w-full px-3 py-2.5 rounded-control border border-gray-300 text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none bg-white">
                        <option value="">Semua Prioritas</option>
                        <option value="tinggi" {{ request('prioritas') === 'tinggi' ? 'selected' : '' }}>Prioritas Tinggi</option>
                        <option value="sedang" {{ request('prioritas') === 'sedang' ? 'selected' : '' }}>Prioritas Sedang</option>
                    </select>
                </div>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs transition">
                    Cari
                </button>
            </form>
        </div>

        <!-- Seksi Antrean Keputusan: Daftar Kartu Laporan (Bagian 6.5.1) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white tracking-tight">
                    Antrean Keputusan
                </h2>
                <span class="text-xs text-gray-400">Menampilkan {{ $laporan->count() }} kasus aktif</span>
            </div>

            @forelse($laporan as $kasus)
                @php
                    $lowongan = $kasus->lowonganKerja;
                    $perusahaan = $lowongan->perusahaan ?? null;
                    $klasifikasi = $kasus->hasilKlasifikasi;
                    $score = $klasifikasi ? $klasifikasi->confidence_score : 0;
                    $isApproved = $kasus->status_pengaduan === 'Tindak Lanjut Disetujui';
                @endphp
                <div class="bg-white rounded-card shadow-md p-5 sm:p-6 border border-gray-100 hover:shadow-lg transition flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                {{ $kasus->nomor_tiket }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ $kasus->tanggal_pengaduan->translatedFormat('d M Y, H:i') }} WIB
                            </span>
                            @if($isApproved)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-ok border border-emerald-200">
                                    Tindak Lanjut Disetujui
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-risk-mid border border-amber-200">
                                    Menunggu Keputusan
                                </span>
                            @endif
                        </div>

                        <h3 class="text-lg font-bold text-navy-900 leading-snug">
                            {{ $lowongan->posisi_lowongan ?? 'Posisi Pekerjaan' }}
                        </h3>

                        <div class="text-xs text-gray-600 flex items-center gap-2">
                            <strong class="text-navy-900">{{ $perusahaan->nama_perusahaan ?? 'Perusahaan' }}</strong>
                            <span>•</span>
                            <span>Pelapor: {{ $kasus->pengguna->name ?? 'Masyarakat' }}</span>
                        </div>

                        <!-- Temuan Ringkas -->
                        <p class="text-xs text-gray-500 bg-gray-50 p-2.5 rounded border border-gray-200 line-clamp-2">
                            <strong>Temuan Analis:</strong> {{ $klasifikasi->tinjauanAnalis->catatan_tinjauan ?? 'Terindikasi anomali pungutan biaya dan identitas fiktif. Perlu penetapan tindakan eskalasi hukum.' }}
                        </p>
                    </div>

                    <!-- Right Side: Confidence Score & Action Button -->
                    <div class="flex sm:flex-col md:items-end justify-between items-center gap-3 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-gray-100">
                        <div class="text-left md:text-right">
                            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wider block">Skor Kecurigaan</span>
                            <div class="text-2xl font-black font-mono {{ $score >= 75 ? 'text-risk-high' : 'text-risk-mid' }}">
                                {{ number_format($score, 0) }}%
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('tindak-lanjut.detail', $kasus->id_pengaduan) }}"
                               class="inline-flex items-center px-4 py-2 rounded-control bg-navy-900 hover:bg-navy-800 text-white font-semibold text-xs shadow transition">
                                <span>Lihat Detail</span>
                                <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                            @if($isApproved)
                                <a href="{{ route('buat-surat.index', $kasus->id_pengaduan) }}"
                                   class="inline-flex items-center px-3 py-2 rounded-control bg-teal-600 hover:bg-teal-500 text-white font-semibold text-xs shadow transition"
                                   title="Buat Laporan Polisi">
                                    Surat Polisi →
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-card shadow p-12 text-center text-gray-500">
                    <p class="text-base font-bold text-navy-900">Belum Ada Laporan yang Menunggu Keputusan</p>
                    <p class="text-xs text-gray-400 mt-1">Semua laporan dari analis telah diproses atau belum ada eskalasi baru.</p>
                </div>
            @endforelse

            <div class="pt-4">
                {{ $laporan->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
