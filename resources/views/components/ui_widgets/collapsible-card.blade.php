<div x-data="{ open: {{ $isopen ? 'true' : 'false' }} }" {{ $attributes->merge(['class' => 'mb-4']) }}>
    <div @click="open = !open"
        class="cursor-pointer py-2 px-4 rounded-t-md text-white flex justify-between items-center {{ $headerClass }}">
        <h5 class="text-lg font-semibold capitalize">{{ $title }}</h5>
        <i class="fa-solid fa-chevron-up"  x-show="open"></i>
        <i class="fa-solid fa-chevron-down" x-show="!open"></i>
    </div>
    <div x-show="open"
        class=" py-2 px-4 border border-t-0 rounded-b-md {{ $contentClass }}">
        {{ $slot }}
    </div>
</div>
