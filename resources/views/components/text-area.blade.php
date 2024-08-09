@props(['disabled' => false])

<div class="relative mb-2 mt-2 text-sm sm:text-base">
    <textarea
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'text-sm sm:text-base text-gray-600 focus:outline-none focus:border focus:border-indigo-700 font-normal w-full h-auto flex items-center border-gray-400 rounded border ' ]) !!}
    >
        {{$slot}}
    </textarea>
</div>

