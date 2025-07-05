@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('permissions.roles_and_permissions')}}
    @stop
@endsection

@section('page_title')
    {{ __('permissions.roles_and_permissions') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{__('permissions.roles_and_permissions')}}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Roles Overview -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4">{{__('permissions.roles_overview')}}</h2>
            <div class="space-y-4">
                @foreach($roles as $role)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium text-gray-900">{{__('roles.' . $role->name)}}</h3>
                                <p class="text-sm text-gray-600">
                                    {{$role->permissions->count() . ' ' . __('permissions.permissions')}}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <x-button :islink="true" color_type="info" size="sm"
                                        :outline="true" href="{{route('admin.permissions.role', $role)}}" target="_blank">
                                    <x-slot:icon>
                                        <i class="fas fa-edit me-2"></i>
                                    </x-slot:icon>
                                    {{__('permissions.manage_permissions')}}
                                </x-button>
                            </div>
                        </div>
                        
                        @if($role->permissions->count() > 0)
                            <div class="mt-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(3) as $permission)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{__('permissions.' . $permission->name)}}
                                        </span>
                                    @endforeach
                                    @if($role->permissions->count() > 3)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            +{{$role->permissions->count() - 3}} {{__('permissions.more')}}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Permissions Overview -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4">{{__('permissions.all_permissions')}}</h2>
            <div class="space-y-4">
                @php
                    $groupedPermissions = [];
                    foreach($permissions as $permission) {
                        $parts = explode(' ', $permission->name);
                        $module = $parts[1] ?? 'general';
                        $groupedPermissions[$module][] = $permission;
                    }
                @endphp
                
                @foreach($groupedPermissions as $module => $modulePermissions)
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 mb-3 capitalize">{{__('permissions.module.' . $module)}}</h3>
                        <div class="space-y-2">
                            @foreach($modulePermissions as $permission)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-700">{{__('permissions.' . $permission->name)}}</span>
                                    <span class="text-gray-500">{{$permission->name}}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold mb-4">{{__('permissions.quick_actions')}}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(isset($superAdminRole) && $superAdminRole)
                <x-button 
                    :islink="true"
                    href="{{ route('admin.permissions.role', $superAdminRole) }}"
                    color_type="primary" 
                    size="md">
                    <x-slot:icon>
                        <i class="fas fa-user-shield me-2"></i>
                    </x-slot:icon>
                    {{__('permissions.manage_super_admin')}}
                </x-button>
            @endif
            
            @if(isset($managerRole) && $managerRole)
                <x-button 
                    :islink="true"
                    href="{{ route('admin.permissions.role', $managerRole) }}"
                    color_type="success" 
                    size="md">
                    <x-slot:icon>
                        <i class="fas fa-user-tie me-2"></i>
                    </x-slot:icon>
                    {{__('permissions.manage_manager')}}
                </x-button>
            @endif
            
            @if(isset($ownerRole) && $ownerRole)
                <x-button 
                    :islink="true"
                    href="{{ route('admin.permissions.role', $ownerRole) }}"
                    color_type="warning" 
                    size="md">
                    <x-slot:icon>
                        <i class="fas fa-crown me-2"></i>
                    </x-slot:icon>
                    {{__('permissions.manage_owner')}}
                </x-button>
            @endif
        </div>
        
        @if(!isset($superAdminRole) || !isset($managerRole) || !isset($ownerRole))
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    {{__('permissions.some_roles_missing')}}
                </p>
            </div>
        @endif
    </div>
@endsection 