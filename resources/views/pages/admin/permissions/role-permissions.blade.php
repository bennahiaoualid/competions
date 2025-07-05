@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('permissions.manage_permissions_for')}} {{__('roles.' . $role->name)}}
    @stop
@endsection

@section('page_title')
    {{__('permissions.manage_permissions_for')}} {{__('roles.' . $role->name)}}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <div>
            <h1 class="text-xl font-bold">{{__('permissions.manage_permissions_for')}} {{__('roles.' . $role->name)}}</h1>
            <p class="text-gray-600 mt-1">{{__('permissions.current_permissions')}}: {{$rolePermissions->count()}}</p>
        </div>
        <x-button 
            :islink="true"
            href="{{ route('admin.permissions.index') }}"
            color_type="secondary" 
            size="sm">
            <x-slot:icon>
                <i class="fas fa-arrow-left me-2"></i>
            </x-slot:icon>
            {{__('permissions.back_to_permissions')}}
        </x-button>
    </div>

    <!-- Role Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{__('roles.' . $role->name)}}</h2>
                <p class="text-gray-600">{{__('permissions.role_description')}}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-blue-600">{{$rolePermissions->count()}}</div>
                <div class="text-sm text-gray-500">{{__('permissions.assigned_permissions')}}</div>
            </div>
        </div>
    </div>

    <!-- Permission Assignment Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">{{__('permissions.assign_permissions')}}</h3>
        
        <form id="permission-form" method="POST" action="{{ route('admin.permissions.assign', $role) }}" class="space-y-6">
            @csrf
            @method('POST')
            
            <!-- Permission Groups -->
            @php
                $groupedPermissions = [];
                foreach($allPermissions as $permission) {
                    $parts = explode(' ', $permission->name);
                    $module = $parts[1] ?? 'general';
                    $groupedPermissions[$module][] = $permission;
                }
            @endphp
            
            @foreach($groupedPermissions as $module => $modulePermissions)
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-medium text-gray-900 capitalize">{{__('permissions.module.' . $module)}}</h4>
                        <div class="flex items-center gap-4">
                            <button type="button" 
                                    class="text-sm text-blue-600 hover:text-blue-800 select-all-module" 
                                    data-module="{{$module}}">
                                {{__('permissions.select_all')}}
                            </button>
                            <button type="button" 
                                    class="text-sm text-red-600 hover:text-red-800 deselect-all-module" 
                                    data-module="{{$module}}">
                                {{__('permissions.deselect_all')}}
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($modulePermissions as $permission)
                            <div class="flex items-center gap-2">
                                <input type="checkbox" 
                                       id="permission_{{ $permission->id }}" 
                                       name="permission_ids[]" 
                                       value="{{ $permission->id }}"
                                       class="permission-checkbox module-{{$module}} rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       {{ $rolePermissions->contains($permission->id) ? 'checked' : '' }}>
                                <label for="permission_{{ $permission->id }}" class="ml-2 text-sm text-gray-700">
                                    {{__('permissions.' . $permission->name)}}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
            
            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t">
                <x-button 
                    type="button"
                    color_type="secondary" 
                    size="md"
                    onclick="resetForm()">
                    <x-slot:icon>
                        <i class="fas fa-undo me-2"></i>
                    </x-slot:icon>
                    {{__('permissions.reset')}}
                </x-button>
                
                <x-button 
                    type="submit"
                    form="permission-form"
                    color_type="success" 
                    size="md">
                    <x-slot:icon>
                        <i class="fas fa-save me-2"></i>
                    </x-slot:icon>
                    {{__('permissions.save_permissions')}}
                </x-button>
            </div>
        </form>
    </div>

    <!-- Current Permissions Summary -->
    @if($rolePermissions->count() > 0)
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold mb-4">{{__('permissions.current_permissions')}}</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($rolePermissions as $permission)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mx-1"></i>
                        {{__('permissions.' . $permission->name)}}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
@endsection

@section('custom_js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all permissions in a module
    document.querySelectorAll('.select-all-module').forEach(button => {
        button.addEventListener('click', function() {
            const module = this.dataset.module;
            document.querySelectorAll(`.module-${module}`).forEach(checkbox => {
                checkbox.checked = true;
            });
        });
    });

    // Deselect all permissions in a module
    document.querySelectorAll('.deselect-all-module').forEach(button => {
        button.addEventListener('click', function() {
            const module = this.dataset.module;
            document.querySelectorAll(`.module-${module}`).forEach(checkbox => {
                checkbox.checked = false;
            });
        });
    });

    // Form submission with confirmation
    document.getElementById('permission-form').addEventListener('submit', function(e) {
        const checkedPermissions = document.querySelectorAll('input[name="permission_ids[]"]:checked');
        if (checkedPermissions.length === 0) {
            e.preventDefault();
            alert('{{__("permissions.select_at_least_one")}}');
            return;
        }
        
        if (!confirm('{{__("permissions.confirm_save")}}')) {
            e.preventDefault();
        }
    });
});

function resetForm() {
    if (confirm('{{__("permissions.confirm_reset")}}')) {
        document.getElementById('permission-form').reset();
        // Restore original checked state
        @foreach($rolePermissions as $permission)
            document.getElementById('permission_{{ $permission->id }}').checked = true;
        @endforeach
    }
}
</script>
@endsection 