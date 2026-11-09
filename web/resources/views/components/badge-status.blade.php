@props([
    'status' => null,
    'size' => 'sm',
    'dot' => true,
])

@php
    use App\Enums\StatusKasus;

    $statusValue = $status instanceof StatusKasus
        ? $status->value
        : ($status ?? (string) $slot);

    // Pemetaan warna berdasarkan status resmi kasus LokerLens (StatusKasus)
    $colorClasses = match ($statusValue) {
        StatusKasus::SelesaiRisikoRendah->value => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        StatusKasus::PerluTinjauan->value => 'bg-amber-100 text-amber-900 border-amber-300',
        StatusKasus::SedangDitinjau->value => 'bg-blue-100 text-blue-800 border-blue-200',
        StatusKasus::TidakTerbukti->value => 'bg-slate-100 text-slate-700 border-slate-300',
        StatusKasus::MenungguKeputusan->value => 'bg-orange-100 text-orange-900 border-orange-300',
        StatusKasus::TindakLanjutDisetujui->value => 'bg-teal-100 text-teal-800 border-teal-300',
        StatusKasus::TidakDitindaklanjuti->value => 'bg-zinc-100 text-zinc-700 border-zinc-300',
        StatusKasus::Selesai->value => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $dotColor = match ($statusValue) {
        StatusKasus::SelesaiRisikoRendah->value => 'bg-emerald-500',
        StatusKasus::PerluTinjauan->value => 'bg-amber-500',
        StatusKasus::SedangDitinjau->value => 'bg-blue-500',
        StatusKasus::TidakTerbukti->value => 'bg-slate-400',
        StatusKasus::MenungguKeputusan->value => 'bg-orange-500',
        StatusKasus::TindakLanjutDisetujui->value => 'bg-teal-500',
        StatusKasus::TidakDitindaklanjuti->value => 'bg-zinc-400',
        StatusKasus::Selesai->value => 'bg-emerald-600',
        default => 'bg-gray-400',
    };

    $sizeClasses = match ($size) {
        'xs' => 'text-[11px] px-2 py-0.5',
        'md' => 'text-sm px-3.5 py-1.5',
        'lg' => 'text-base px-4 py-2',
        default => 'text-xs px-2.5 py-1',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-semibold rounded-full border {$colorClasses} {$sizeClasses}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full mr-1.5 shrink-0 {{ $dotColor }}" aria-hidden="true"></span>
    @endif
    <span>{{ $statusValue }}</span>
</span>
