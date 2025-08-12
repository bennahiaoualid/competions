@props([
    'name',
    'value' => '',
    'model' => 'selectedValue',
    'grid' => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 items-stretch',
    'gap' => 'gap-4'
])

<div x-data="{ {{ $model }}: '{{ $value }}' }" class="mt-6">
    <input type="hidden" name="{{ $name }}" x-model="{{ $model }}">
    
    <div class="mt-2 grid {{ $grid }} {{ $gap }}">
        {{ $slot }}
    </div>
    
    @error($name)
        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
    @enderror
</div> 