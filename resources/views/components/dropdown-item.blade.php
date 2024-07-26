@props([
    'active' => false,
    'tag' => 'a', // Default to 'a', can be set to 'button' for buttons
    'href' => '#', // Default href for links
    'formAction' => '', // Action URL for form submission
])
@php
    $active_classes = $active ? 'bg-indigo-100 text-indigo-600' : 'text-gray-700';
    $base_classes = 'truncate mb-1 px-4 py-1 block hover:bg-indigo-100 transition duration-200 ease-out ' . $active_classes;
@endphp

@if ($tag === 'a')
    <a {{ $attributes->merge(['class' => $base_classes, 'href' => $href]) }}>
        {{ $slot }}
    </a>
@elseif ($tag === 'button')
    <form method="POST" action="{{ $formAction }}">
        @csrf
        <button type="submit" {{ $attributes->merge(['class' => $base_classes]) }}>
            {{ $slot }}
        </button>
    </form>
@endif
