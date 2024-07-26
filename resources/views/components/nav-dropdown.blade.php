@props(['title', 'href', 'links' => [], 'active', 'sub'])

@php
    $dropdownId = Str::slug($title);
    $linkClasses = ($active ?? false)
                    ? 'nav-link active py-2 px-4 rounded flex items-center bg-blue-500'
                    : 'nav-link py-2 px-4 hover:bg-gray-700 rounded flex items-center';
@endphp

<li class="mb-2" x-data="{ open: @js($active) }">
    <x-nav-link href="#" @click="open = !open"  :active="$active" :sub="false" data-dropdown-target="dropdown-{{ $dropdownId }}">
       @isset($icon) {{ $icon }} @endisset {{ $title }}
        <i class="fas fa-chevron-down arrow-icon transition duration-300 ms-auto" :class="open ? 'rotate-0' : 'ltr:rotate-90 rtl:-rotate-90'"></i>
    </x-nav-link>
    @if(!empty($links))
        <ul x-show="open" @click.outside="open = false" @close.stop="open = false" data-dropdown="dropdown-{{ $dropdownId }}" class="sub-menu mt-1 space-y-1">
            @foreach($links as $link)
                <li>
                    <x-nav-link :href="$link['url']" :active="$link['active']" :sub="$link['subnav']">
                        <i class="far fa-circle nav-icon me-2 text-lg"></i> {{ $link['title'] }}
                    </x-nav-link>
                </li>
            @endforeach
        </ul>
    @endif
</li>

