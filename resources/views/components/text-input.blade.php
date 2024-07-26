@props(['disabled' => false, "placeholder" => "", 'icon'=> false])

@php
    $classes = ($icon ?? false)
                    ? 'ps-12'
                    : '';
@endphp
<div class="relative mb-2 mt-2 text-sm sm:text-base">
    @isset($input_icon)
        <div class="absolute text-gray-600 flex items-center px-4 border-e h-full">
                {{ $input_icon }}
        </div>
    @endisset
    <input
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge(['class' => 'text-sm sm:text-base text-gray-600 focus:outline-none focus:border focus:border-indigo-700 font-normal w-full h-10 flex items-center border-gray-400 rounded border ' . $classes
                                    ,'placeholder'=>$placeholder]) !!}
    />
</div>
