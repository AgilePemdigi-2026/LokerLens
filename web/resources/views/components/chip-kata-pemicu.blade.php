@props([
    'kata' => null,
    'bobot' => null,
    'kategori' => null,
    'tipe' => 'chip', // 'chip' (pill ringkas) atau 'card' (kartu detail indikator)
])

@php
    $kataText = $kata ?? (string) $slot;

    // Pemformatan bobot indikator
    $formattedBobot = null;
    if ($bobot !== null && $bobot !== '') {
        if (is_numeric($bobot)) {
            $num = (float) $bobot;
            $formattedBobot = ($num > 0 ? '+' : '') . (floor($num) == $num ? (int) $num : number_format($num, 1)) . '%';
        } else {
            $formattedBobot = (string) $bobot;
        }
    }
@endphp

@if($tipe === 'card')
    <!-- Tampilan Mode Kartu Indikator Detail (Sesuai Halaman Hasil & Detail Analis) -->
    <div {{ $attributes->merge(['class' => 'border border-red-200 bg-red-50/70 rounded-control p-3.5 flex items-start space-x-3 shadow-sm text-navy-900']) }}>
        <span class="w-6 h-6 rounded-full bg-risk-high text-white flex items-center justify-center text-xs shrink-0 mt-0.5 font-bold" aria-hidden="true">
            !
        </span>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
                <h4 class="text-xs sm:text-sm font-bold text-red-950 truncate">
                    {{ $kataText }}
                </h4>
                @if($kategori)
                    <span class="text-[10px] sm:text-[11px] font-semibold text-risk-high bg-white px-2 py-0.5 rounded border border-red-200 shrink-0">
                        {{ $kategori }}
                    </span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs text-red-800">
                <span class="font-mono">
                    Kata pemicu: <strong>"{{ $kataText }}"</strong>
                </span>
                @if($formattedBobot)
                    <span class="font-mono font-bold bg-white text-risk-high px-2 py-0.5 rounded border border-red-200 text-[11px]">
                        Bobot: {{ $formattedBobot }}
                    </span>
                @endif
            </div>
        </div>
    </div>
@else
    <!-- Tampilan Mode Chip / Pill Ringkas (Default) -->
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 text-red-950 border border-red-200 text-xs shadow-sm']) }}>
        <span class="w-1.5 h-1.5 rounded-full bg-risk-high shrink-0" aria-hidden="true"></span>
        <span class="font-bold text-navy-900">{{ $kataText }}</span>
        @if($kategori)
            <span class="text-[10px] text-gray-500 font-normal">({{ $kategori }})</span>
        @endif
        @if($formattedBobot)
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-white text-risk-high border border-red-200">
                {{ $formattedBobot }}
            </span>
        @endif
    </div>
@endif
