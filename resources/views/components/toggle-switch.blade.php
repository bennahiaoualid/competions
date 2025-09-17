@props([
    'name',
    'label' => '',
    'checked' => false,
    'disabled' => false,
])
<label class="flex items-center {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
    <div class="me-3 text-sm text-gray-700">{{ $label }}</div>
    <div class="relative">
        <input type="hidden" name="{{ $name }}" value="0">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            class="sr-only peer"
        >
        <div class="w-11 h-6 rounded-full transition-colors 
                    peer bg-gray-300 peer-checked:bg-blue-600 
                    peer-disabled:bg-gray-200">
        </div>
        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform 
                    peer-checked:translate-x-full peer-disabled:bg-gray-100">
        </div>
    </div>
</label>

