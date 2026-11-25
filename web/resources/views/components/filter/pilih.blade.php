@props([
    'name',
    'label' => null,
])

{{-- Dropdown di dalam <x-filter>. Slot berisi <option>. --}}
<div class="w-full md:w-auto md:flex-1 md:min-w-[10rem]">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}" class="w-full pl-3 pr-8 py-2 rounded-control border border-gray-300 bg-white text-xs sm:text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none">
        {{ $slot }}
    </select>
</div>
