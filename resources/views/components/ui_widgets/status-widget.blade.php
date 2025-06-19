@props(['status' => 'inactive', 'outline' => false, 'text' => ''])
@php

    switch ($status) {
        case 'active':
            $color = $outline ? 'bg-green-100' : 'bg-green-300';
            $color_border = 'border-green-300';
            break;
        case 'inactive':
            $color = $outline ? 'bg-red-100' : 'bg-red-300';
            $color_border = 'border-red-300';
            break;
        case 'finished':
            $color = $outline ? 'bg-sky-100' : 'bg-sky-300';
            $color_border = 'border-sky-300';
            break;
        default:
            $color = '';
            $color_border = '';
    }
@endphp

<div class="text-nowrap">
    <span {{ $attributes->merge(['class' => 'block py-1 px-3 text-center text-sm ' . $color . ' rounded-full font-semibold ' . ($outline ? ' border-2 ' . $color_border : '')]) }}>
        {{ $text }}
    </span>
</div>
