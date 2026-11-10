@props([
    'skor' => 0,
    'tipe' => 'bar', // 'bar' atau 'circle' / 'lingkaran'
    'label' => 'Probabilitas Penipuan',
    'sublabel' => null,
    'kategori' => null,
])

@php
    $skorFloat = (float) $skor;
    $skorClamped = max(0, min(100, $skorFloat));
    $ambang = (float) config('lokerlens.ambang_prioritas', 70);

    // Kategori risiko jika tidak ditentukan secara eksplisit
    if ($kategori === null) {
        if ($skorClamped >= $ambang) {
            $kategori = $skorClamped >= 85 ? 'Kritis' : 'Risiko Tinggi';
        } elseif ($skorClamped >= 40) {
            $kategori = 'Risiko Sedang';
        } else {
            $kategori = 'Risiko Rendah';
        }
    }

    // Penentuan skema warna (mengikuti token Tailwind & CSS LokerLens)
    $isHigh = $skorClamped >= $ambang;
    $isMid  = ! $isHigh && $skorClamped >= 40;

    $textColorClass = $isHigh ? 'text-risk-high' : ($isMid ? 'text-risk-mid' : 'text-risk-low');
    $barBgClass    = $isHigh ? 'bg-risk-high' : ($isMid ? 'bg-risk-mid' : 'bg-risk-low');
    $badgeBgClass  = $isHigh ? 'bg-red-100 text-risk-high border-red-200' : ($isMid ? 'bg-amber-100 text-risk-mid border-amber-200' : 'bg-emerald-100 text-risk-low border-emerald-200');
    $hexStroke     = $isHigh ? '#c0392b' : ($isMid ? '#d68910' : '#1e8449');

    // Perhitungan lingkaran SVG (r = 40, keliling = 251.327)
    $circumference = 251.327;
    $strokeOffset = $circumference - ($circumference * ($skorClamped / 100));
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-card p-5 border border-gray-100 shadow-sm text-navy-900']) }}>
    @if(in_array($tipe, ['circle', 'lingkaran'], true))
        <!-- Tampilan Lingkaran (Circular Gauge) -->
        <div class="flex flex-col sm:flex-row items-center gap-5">
            <!-- Lingkaran SVG -->
            <div class="relative w-28 h-28 shrink-0">
                <svg class="w-full h-full -rotate-90 transform" viewBox="0 0 100 100" aria-label="Skor risiko {{ number_format($skorClamped, 0) }} persen">
                    <!-- Lingkaran latar belakang -->
                    <circle
                        cx="50"
                        cy="50"
                        r="40"
                        stroke="#e5e7eb"
                        stroke-width="8"
                        fill="transparent"
                    />
                    <!-- Lingkaran progres -->
                    <circle
                        cx="50"
                        cy="50"
                        r="40"
                        stroke="{{ $hexStroke }}"
                        stroke-width="8"
                        stroke-linecap="round"
                        fill="transparent"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $strokeOffset }}"
                        class="transition-all duration-700 ease-out"
                    />
                </svg>
                <!-- Teks Skor di Tengah Lingkaran -->
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-black font-mono tracking-tight {{ $textColorClass }}">
                        {{ number_format($skorClamped, 0) }}%
                    </span>
                    <span class="text-[9px] uppercase font-bold text-gray-400 -mt-0.5">Skor</span>
                </div>
            </div>

            <!-- Keterangan Skor Lingkaran -->
            <div class="flex-1 text-center sm:text-left space-y-1.5">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeBgClass }}">
                        {{ $kategori }}
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">
                        {{ $label }}
                    </span>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed">
                    {{ $sublabel ?? ($isHigh ? 'Tingkat indikasi bahaya melebihi ambang batas pengawasan.' : ($isMid ? 'Perlu kewaspadaan terhadap anomali pada penawaran.' : 'Tidak ditemukan pola anomali yang mencurigakan.')) }}
                </p>
            </div>
        </div>
    @else
        <!-- Tampilan Bar Progres Linear (Default) -->
        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 block">
                        {{ $label }}
                    </span>
                    @if($sublabel)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $sublabel }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeBgClass }}">
                        {{ $kategori }}
                    </span>
                    <span class="text-2xl sm:text-3xl font-black font-mono tracking-tight {{ $textColorClass }}">
                        {{ number_format($skorClamped, 0) }}%
                    </span>
                </div>
            </div>

            <!-- Bar Progres -->
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div
                    class="h-3 rounded-full transition-all duration-700 ease-out {{ $barBgClass }}"
                    style="width: {{ $skorClamped }}%"
                    role="progressbar"
                    aria-valuenow="{{ $skorClamped }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>

            <!-- Skala 0% - 50% - 100% -->
            <div class="flex justify-between text-[11px] text-gray-400 font-medium pt-0.5">
                <span>0% (Aman)</span>
                <span>50% (Perlu Diwaspadai)</span>
                <span>100% (Sangat Mencurigakan)</span>
            </div>
        </div>
    @endif
</div>
