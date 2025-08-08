@props([
    'value',
    'selected' => false,
    'border' => 'primary',
    'model' => 'selectedValue'
])

@php
    $borderClasses = match($border) {
        'primary' => 'border-primary',
        'success' => 'border-success',
        'warning' => 'border-warning',
        'danger' => 'border-danger',
        'gray' => 'border-gray-500',
        default => $border
    };

@endphp

<div class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-blue-500 transition-all duration-200"
     :class="{ '{{ $borderClasses }}': {{ $model }} === '{{ $value }}' }"
     @click="{{ $model }} = '{{ $value }}'">
    
    {{ $slot }}
    
    <span class="pointer-events-none absolute -inset-px rounded-lg border-2 transition-all duration-200" 
          :class="{ '{{ $borderClasses }}': {{ $model }} === '{{ $value }}', 'border-transparent': {{ $model }} !== '{{ $value }}' }" 
          aria-hidden="true"></span>
</div> 