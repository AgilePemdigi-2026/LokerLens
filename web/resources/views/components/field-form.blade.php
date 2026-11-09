@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'hint' => null,
    'badge' => null,
    'rows' => 4,
    'options' => [],
    'id' => null,
    'error' => null,
])

@php
    $inputId = $id ?? $name;
    $currentValue = old($name, $value ?? '');
    $errorsBag = $errors ?? session('errors') ?? new \Illuminate\Support\ViewErrorBag;
    $errorMessage = $error ?? ($errorsBag->has($name) ? $errorsBag->first($name) : null);
    $hasError = ! empty($errorMessage);

    $baseClass = 'w-full rounded-control border text-sm text-navy-900 focus:ring-2 focus:ring-teal-600 focus:outline-none transition disabled:bg-gray-100';
    $stateClass = $hasError ? 'border-risk-high ring-1 ring-risk-high' : 'border-gray-300';
@endphp

<div class="w-full">
    @if($label)
        <div class="flex justify-between items-center mb-1">
            <label for="{{ $inputId }}" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                {{ $label }}
                @if($required)
                    <span class="text-risk-high" aria-hidden="true">*</span>
                @endif
            </label>
            @if($badge)
                <span class="text-xs text-gray-500 font-medium">{{ $badge }}</span>
            @endif
        </div>
    @endif

    @if($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $inputId }}"
            rows="{{ $rows }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => "{$baseClass} {$stateClass} px-4 py-3"]) }}
        >{{ $currentValue }}</textarea>
    @elseif($type === 'select')
        <select
            name="{{ $name }}"
            id="{{ $inputId }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => "{$baseClass} {$stateClass} px-3 py-2.5 bg-white"]) }}
        >
            @if(!empty($options))
                @foreach($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" {{ (string) $currentValue === (string) $optValue ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                @endforeach
            @else
                {{ $slot }}
            @endif
        </select>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            value="{{ $currentValue }}"
            @if($required) required @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => "{$baseClass} {$stateClass} px-4 py-2.5"]) }}
        />
    @endif

    @if($hasError)
        <p class="text-xs text-risk-high font-semibold mt-1 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @elseif($hint)
        <p class="text-[11px] text-gray-500 mt-1">{{ $hint }}</p>
    @endif
</div>
