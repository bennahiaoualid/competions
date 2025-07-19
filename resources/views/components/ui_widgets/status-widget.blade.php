@props(['status' => 'inactive', 'outline' => false, 'text' => ''])

@php
    switch ($status) {
        case 'active':
        case 'processing':
        case 'approved':
        case 'ready':
            $color = $outline ? 'bg-green-100' : 'bg-green-300';
            $color_border = 'border-green-300';
            break;
        case 'rejected':
        case 'failed':
            $color = $outline ? 'bg-red-100' : 'bg-red-300';
            $color_border = 'border-red-300';
            break;
        case 'finished':
        case 'completed':
            $color = $outline ? 'bg-sky-100' : 'bg-sky-300';
            $color_border = 'border-sky-300';
            break;
        case 'pending':
            $color = $outline ? 'bg-yellow-100' : 'bg-yellow-300';
            $color_border = 'border-yellow-300';
            break;
        default:
            $color = 'bg-gray-200';
            $color_border = 'border-gray-300';
    }
@endphp

<div class="text-nowrap">
    <span {{ $attributes->merge(['class' => 'block capitalize py-1 px-3 text-center text-sm ' . $color . ' rounded-full font-semibold ' . ($outline ? ' border-2 ' . $color_border : '')]) }}>
        {{ $text }}
    </span>
</div>

