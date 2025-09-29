@props([
    "type" => 'info',
    "outline" => false,
    "closable" => false,
    "title" => '',
])

@php
    $base_classes = "rounded-md px-2 py-4 sm:px-4 mb-4 flex items-start";
    $size_classes = "text-sm md:text-base";

    // Color classes based on type
    $color_classes = match($type) {
        'success' => $outline
            ? "border border-green-500 text-green-700 bg-green-100"
            : "bg-green-500 text-white",
        'danger' => $outline
            ? "border border-red-500 text-red-700 bg-red-100"
            : "bg-red-500 text-white",
        'warning' => $outline
            ? "border border-yellow-500 text-yellow-700 bg-yellow-100"
            : "bg-yellow-500 text-white",
        'info' => $outline
            ? "border border-blue-500 text-blue-700 bg-blue-100"
            : "bg-blue-500 text-white",
        default => $outline
            ? "border border-gray-500 text-gray-700 bg-gray-100"
            : "bg-gray-500 text-white",
    };

    // Icon classes based on type
    $icon_classes = match($type) {
        'success' => "fa-check-circle text-green-500",
        'danger' => "fa-exclamation-circle text-red-500",
        'warning' => "fa-exclamation-triangle text-yellow-500",
        'info' => "fa-info-circle text-blue-500",
        default => "fa-info-circle text-gray-500",
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"

    {{ $attributes->merge(['class' => $base_classes . ' ' . $size_classes . ' ' . $color_classes]) }}
>
    <div class="flex-shrink-0">
        <i class="fas {{ $icon_classes }} me-3"></i>
    </div>
    <div class="flex-1">
        @if($title)
            <h4 class="mb-2 font-bold capitalize text-sm md:text-base lg:text-lg">{{ $title }}</h4>
        @endif
        {{ $slot }}
    </div>
    @if($closable)
        <div class="flex-shrink-0 me-4">
            <button
                type="button"
                @click="show = false"
                class="text-lg font-semibold"
            >&times;</button>
        </div>
    @endif
</div>

