@props(['status' => 'inactive', 'outline' => true])
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
            $color = 'bg-gray-100';
             $color_border = 'border-gray-300';
            $text = 'Unknown';
    }
@endphp

<div class="">
    <span class="block py-1 px-3 text-center text-sm {{ $color }} rounded-full font-semibold {{$outline ? 'border-2' . $color_border : ''}}">
        {{ $text }}
    </span>
</div>
