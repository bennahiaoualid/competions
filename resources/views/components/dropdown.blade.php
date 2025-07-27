@props(['alignment' => 'right', 'width' => 'min-w-48', 'contentClasses' => 'py-1 bg-white'])

@php
// Mobile-first responsive alignment classes
switch ($alignment) {
    case 'left':
        // Mobile: full screen width, positioned below trigger
        // Desktop: left-aligned, fallback to right if no space
        $alignmentClasses = 'sm:w-auto sm:origin-top-left sm:start-0 sm:top-auto sm:mt-2';
        break;
    case 'top':
        $alignmentClasses = 'sm:w-auto sm:origin-top sm:bottom-auto sm:mt-2';
        break;
    case 'right':
    default:
        // Mobile: full screen width, positioned below trigger
        // Desktop: right-aligned, fallback to left if no space
        $alignmentClasses = 'sm:w-auto sm:origin-top-right sm:end-0 sm:top-auto sm:mt-2';
        break;
}
@endphp

<div class="relative" 
     x-data="{ 
         open: false,
         alignment: '{{ $alignment }}',
         checkSpace() {
             const dropdown = this.$refs.dropdown;
             const trigger = this.$refs.trigger;
             const viewportWidth = window.innerWidth;
             const viewportHeight = window.innerHeight;
             const triggerRect = trigger.getBoundingClientRect();
             const dropdownWidth = dropdown.offsetWidth;
             const dropdownHeight = dropdown.offsetHeight;
             
             if (window.innerWidth >= 640) { // sm breakpoint - Desktop logic
                 // Check if there's enough space on the preferred side
                 if (this.alignment === 'right') {
                     const spaceOnRight = viewportWidth - triggerRect.right;
                     if (spaceOnRight < dropdownWidth) {
                         // Not enough space on right, switch to left
                         dropdown.classList.remove('sm:end-0', 'sm:origin-top-right');
                         dropdown.classList.add('sm:start-0', 'sm:origin-top-left');
                     }
                 } else if (this.alignment === 'left') {
                     const spaceOnLeft = triggerRect.left;
                     if (spaceOnLeft < dropdownWidth) {
                         // Not enough space on left, switch to right
                         dropdown.classList.remove('sm:start-0', 'sm:origin-top-left');
                         dropdown.classList.add('sm:end-0', 'sm:origin-top-right');
                     }
                 }
             } else { // Mobile logic
                 // Check if dropdown would overflow bottom of viewport
                 const spaceBelow = viewportHeight - triggerRect.bottom;
                 
                 // Mobile: Absolute positioning with calculated offsets
                 dropdown.style.position = 'absolute';
                 dropdown.style.width = '100vw';
                 
                 // Calculate left offset: negative of trigger's left position
                 const leftOffset = -triggerRect.left;
                 dropdown.style.left = `${leftOffset}px`;
                 
                 if (spaceBelow < dropdownHeight) {
                     // Not enough space below, position above trigger
                     dropdown.style.top = 'auto';
                     dropdown.style.bottom = '100%';
                 } else {
                     // Enough space below, position below trigger
                     dropdown.style.top = '100%';
                     dropdown.style.bottom = 'auto';
                 }
             }
         },
         updatePosition() {
             if (window.innerWidth < 640 && this.open) {
                 this.checkSpace();
             }
         }
     }" 
     @click.outside="open = false" 
     @close.stop="open = false"
     @scroll.window="updatePosition()">
    
    <div @click="open = ! open" x-ref="trigger">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-ref="dropdown"
         x-init="$nextTick(() => checkSpace())"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         {{ $attributes->merge(['class' => "absolute z-50 mb-2 mt-0 sm:mt-2 sm:mb-0 $width max-w-full rounded-md shadow-lg $alignmentClasses"]) }}
         style="display: none;"
         @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $slot }}
        </div>
    </div>
</div>

{{-- 
OLD CODE (BEFORE MODIFICATION):

@props(['alignment' => 'right', 'width' => 'min-w-48',  'contentClasses' => 'py-1 bg-white'])

@php
switch ($alignment) {
    case 'left':
        $alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'mobile':
        // Mobile: bottom center, Desktop: top-right
        //$alignmentClasses = 'xs:origin-bottom xs:bottom-0 xs:left-1/2 xs:transform xs:-translate-x-1/2 sm:origin-top-right sm:bottom-auto sm:left-auto sm:transform-none sm:end-0';
        break;
    case 'right':
    default:
        $alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
        break;
}

@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            {{ $attributes->merge(['class' => "absolute z-50 xs:mb-2 xs:mt-0 sm:mt-2 sm:mb-0 $width max-w-full rounded-md shadow-lg $alignmentClasses"]) }}
            style="display: none;"
            @click="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $slot }}
        </div>
    </div>
</div>

--}}

{{-- 
PREVIOUS VERSION (BEFORE ABSOLUTE POSITIONING UPDATE):

             } else { // Mobile logic
                 // Check if dropdown would overflow bottom of viewport
                 const spaceBelow = viewportHeight - triggerRect.bottom;
                 
                 // Mobile: Fixed positioning with calculated top position
                 dropdown.style.position = 'fixed';
                 dropdown.style.left = '0px';
                 dropdown.style.width = '100vw';
                 
                 // Account for current scroll position
                 const scrollY = window.scrollY || window.pageYOffset;
                 
                 if (spaceBelow < dropdownHeight) {
                     // Not enough space below, position above trigger
                     dropdown.style.top = `${triggerRect.top - dropdownHeight + scrollY}px`;
                 } else {
                     // Enough space below, position below trigger
                     dropdown.style.top = `${triggerRect.bottom + scrollY}px`;
                 }
             }

--}}
