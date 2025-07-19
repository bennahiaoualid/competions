@php
    $fields = [
        'auditor' => __('admin.availability.auditor'),
        'level_manager' => __('admin.availability.level_manager'),
        'ownership_transfer' => __('admin.availability.ownership_transfer'),
    ];
    $descriptions = [
        'auditor' => __('admin.availability.desc.auditor'),
        'level_manager' => __('admin.availability.desc.level_manager'),
        'ownership_transfer' => __('admin.availability.desc.ownership_transfer'),
    ];
    $hiddenFields = $availability->hidden_fields_for_user;
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ ucwords(__('admin.availability.title')) }}
        </h2>
    </header>

    <div class="mt-6 space-y-6">
        @foreach($fields as $field => $label)
            @if(!in_array($field, $hiddenFields))
                <form method="POST" action="{{ route('admin.availability.update' . ucfirst(Str::camel($field)), $availability) }}" class="mb-4">
                    @csrf
                    @method('PATCH')
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="font-medium">{{ $label }}</label>
                            <div class="text-sm text-gray-500">{{ $descriptions[$field] }}</div>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <x-toggle-switch 
                                name="{{ $field }}" 
                                label="{{ $availability->$field ? __('admin.availability.on') : __('admin.availability.off') }}" 
                                :checked="(bool)$availability->$field" 
                            />
                        </div>
                    </div>
                    <x-button class="mt-2" color_type="primary">{{ __('admin.availability.update') }}</x-button>
                </form>
            @endif
        @endforeach
        @if(session('status'))
            <div class="mt-4 text-green-600">{{ session('status') }}</div>
        @endif
    </div>
</section>