@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm sm:text-base capitalize text-gray-700']) }}>
    {{ $value ?? $slot }}
</label>
