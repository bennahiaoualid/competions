@props(['disabled' => false, 'placeholder' => null, 'options' => [], 'name' => null, 'value' => null])

<div x-data="{
    open: false,
    search: '',
    selected: @js($value),
    options: @js($options),
    dropdownPosition: 'bottom',
    get filteredOptions() {
        if (!this.search) return this.options;
        return this.options.filter(o => o.text.toLowerCase().includes(this.search.toLowerCase()));
    },
    select(option) {
        this.selected = option.value;
        this.open = false;
        this.search = option.text;
        $refs.input.value = option.value;
        $dispatch('input', option.value);
    },
    checkPosition() {
        if (!this.open) return;
        
        const rect = $refs.container.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const dropdownHeight = 240; // max-h-60 = 240px
        
        // Check space below
        const spaceBelow = viewportHeight - rect.bottom;
        // Check space above
        const spaceAbove = rect.top;
        
        if (spaceBelow >= dropdownHeight || spaceBelow > spaceAbove) {
            this.dropdownPosition = 'bottom';
        } else {
            this.dropdownPosition = 'top';
        }
    },
    init() {
        if (this.selected) {
            const found = this.options.find(o => o.value == this.selected);
            if (found) this.search = found.text;
        }
    }
}" x-init="init()" class="relative mb-2 mt-2">
    <input
        x-ref="input"
        type="hidden"
        name="{{ $name }}"
        :value="selected"
        {{ $disabled ? 'disabled' : '' }}
    >
    <input
        x-ref="container"
        type="text"
        x-model="search"
        @focus="open = true; $nextTick(() => checkPosition())"
        @click="open = true; $nextTick(() => checkPosition())"
        @keydown.arrow-down.prevent="open = true; $nextTick(() => { checkPosition(); $refs.listbox.focus(); })"
        :placeholder="'{{ $placeholder ?? 'Select...' }}'"
        class="text-gray-600 focus:outline-none focus:border focus:border-indigo-700 font-normal w-full h-10 flex items-center text-sm border-gray-300 rounded border px-3"
        autocomplete="off"
        {{ $disabled ? 'disabled' : '' }}
    >
    <div
        x-show="open"
        @click.away="open = false"
        :class="{
            'absolute z-10 w-full bg-white border border-gray-300 rounded shadow-lg max-h-60 overflow-auto': true,
            'mt-1': dropdownPosition === 'bottom',
            'mb-1 bottom-full': dropdownPosition === 'top'
        }"
    >
        <ul tabindex="-1" x-ref="listbox">
            <template x-if="filteredOptions.length === 0">
                <li class="px-4 py-2 text-gray-400">No results found</li>
            </template>
            <template x-for="option in filteredOptions" :key="option.value">
                <li
                    @click="select(option)"
                    :class="{'bg-indigo-100 text-indigo-700': selected == option.value, 'cursor-pointer': true, 'px-4 py-2': true}"
                    x-text="option.text"
                ></li>
            </template>
        </ul>
    </div>
</div> 