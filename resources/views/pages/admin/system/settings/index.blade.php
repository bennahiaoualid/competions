@extends('layouts.admin.master')

@section('title')
    {{__('settings.title')}}
@endsection

@section('page_title')
    {{ __('settings.title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <div>
            <h1 class="text-xl font-bold">{{__('settings.title')}}</h1>
            <p class="text-gray-600">{{__('settings.subtitle')}}</p>
        </div>
        <div class="flex space-x-2">
            <form method="POST" action="{{ route('admin.system.settings.refresh-cache') }}" class="inline">
                @csrf
                <x-button type="submit" color_type="secondary">
                    <i class="fas fa-sync-alt me-2"></i>
                    {{__('settings.common.refresh')}}
                </x-button>
            </form>
        </div>
    </div>

    {{-- Payment Rules Section --}}
    <div class="mb-8 border-t-4 border-primary pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 bg-blue-100 rounded-lg">
                <i class="fas fa-credit-card text-blue-600 text-xl"></i>
            </div>
            <div class="ml-3 space-y-2">
                <h2 class="text-xl font-semibold text-gray-900">{{__('settings.categories.payment.title')}}</h2>
                <p class="text-sm text-gray-600">{{ __('settings.categories.payment.description') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($paymentSettings as $setting)
                <div class="bg-white rounded-lg shadow-md p-2 md:p-6 border border-gray-200">
                    <div class="flex items-center mb-4 gap-2">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1 space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __("settings.payment.{$setting->setting_key}.name") }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ __("settings.payment.{$setting->setting_key}.description") }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{__('settings.common.current_value')}}:</span>
                            <span class="font-semibold text-lg text-gray-900">{{ $setting->setting_value }}</span>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p><strong>{{__('settings.common.unit')}}:</strong> {{ __("settings.payment.{$setting->setting_key}.unit") }}</p>
                            <p><strong>{{__('settings.common.help')}}:</strong> {{ __("settings.payment.{$setting->setting_key}.help") }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.system.settings.update', $setting->setting_key) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div>
                                <x-input-label for="value_{{ $setting->setting_key }}" :value=" ucwords(__('settings.common.new_value'))" />
                                <x-text-input 
                                        id="value_{{ $setting->setting_key }}" 
                                        type="text" class="mt-1 block w-full" 
                                        :value="$setting->setting_value"
                                        name="value"
                                        />
                                <x-input-error :messages="$errors->get($setting->setting_key)" class="mt-2" />
                            </div>
                            
                            <div class="flex justify-end">
                                <x-button type="submit" color_type="primary">
                                    <x-slot:icon>
                                        <i class="fas fa-save me-2"></i>
                                    </x-slot:icon>
                                    {{__('settings.common.update')}}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- System Configuration Section --}}
    <div class="mb-8 border-t-4 border-success pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 bg-green-100 rounded-lg">
                <i class="fas fa-cogs text-green-600 text-xl"></i>
            </div>
            <div class="ml-3 space-y-2">
                <h2 class="text-xl font-semibold text-gray-900">{{__('settings.categories.system.title')}}</h2>
                <p class="text-sm text-gray-600">{{ __('settings.categories.system.description') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($systemSettings as $setting)
                <div class="bg-white rounded-lg shadow-md p-2 md:p-6 border border-gray-200">
                    <div class="flex items-center mb-4 gap-2">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-cogs text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1 space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __("settings.system.{$setting->setting_key}.name") }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ __("settings.system.{$setting->setting_key}.description") }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{__('settings.common.current_value')}}:</span>
                            <span class="font-semibold text-lg text-gray-900">
                                @if($setting->setting_key === 'maintenance_mode')
                                    <span class="{{ $setting->setting_value === 'true' ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $setting->setting_value === 'true' ? 'Enabled' : 'Disabled' }}
                                    </span>
                                @elseif($setting->setting_key === 'max_file_upload_size')
                                    {{ number_format($setting->setting_value) }} B
                                @elseif($setting->setting_key === 'session_timeout')
                                    {{ $setting->setting_value }} min
                                @else
                                    {{ $setting->setting_value }}
                                @endif
                            </span>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p><strong>{{__('settings.common.unit')}}:</strong> {{ __("settings.system.{$setting->setting_key}.unit") }}</p>
                            <p><strong>{{__('settings.common.help')}}:</strong> {{ __("settings.system.{$setting->setting_key}.help") }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.system.settings.update', $setting->setting_key) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            
                            <div>
                                <x-input-label for="value_{{ $setting->setting_key }}" :value=" ucwords(__('settings.common.new_value'))" />
                                @if($setting->setting_key === 'maintenance_mode')
                                <x-form.select-box id="value_{{ $setting->setting_key }}" name="value"  :options="[
                                    ['value' => 'false', 'text' => __('settings.common.disabled'), 'selected' => $setting->setting_value === 'false'],
                                    ['value' => 'true', 'text' => __('settings.common.enabled'), 'selected' => $setting->setting_value === 'true'],
                                ]">
                                </x-form.select-box>
                                @else
                                <x-text-input 
                                        id="value_{{ $setting->setting_key }}" 
                                        type="text" class="mt-1 block w-full" 
                                        :value="$setting->setting_value"
                                        name="value"
                                        />
                                @endif
                                <x-input-error :messages="$errors->get($setting->setting_key)" class="mt-2" />
                            </div>

                            <div class="flex justify-end">
                                <x-button type="submit" color_type="success">
                                    <x-slot:icon>
                                        <i class="fas fa-save me-2"></i>
                                    </x-slot:icon>
                                    {{__('settings.common.update')}}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Notification Settings Section --}}
    <div class="mb-8 border-t-4 border-purple-600 pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 bg-purple-100 rounded-lg">
                <i class="fas fa-bell text-purple-600 text-xl"></i>
            </div>
            <div class="ml-3 space-y-2">
                <h2 class="text-xl font-semibold text-gray-900">{{__('settings.categories.notifications.title')}}</h2>
                <p class="text-sm text-gray-600">{{ __('settings.categories.notifications.description') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($notificationSettings as $setting)
                <div class="bg-white rounded-lg shadow-md p-2 md:p-6 border border-gray-200">
                    <div class="flex items-center mb-4 gap-2">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-bell text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1 space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __("settings.notifications.{$setting->setting_key}.name") }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ __("settings.notifications.{$setting->setting_key}.description") }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{__('settings.common.current_value')}}:</span>
                            <span class="font-semibold text-lg text-gray-900 {{ $setting->setting_value === 'true' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $setting->setting_value === 'true' ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p><strong>{{__('settings.common.unit')}}:</strong> {{ __("settings.notifications.{$setting->setting_key}.unit") }}</p>
                            <p><strong>{{__('settings.common.help')}}:</strong> {{ __("settings.notifications.{$setting->setting_key}.help") }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.system.settings.update', $setting->setting_key) }}" class="space-y-3">
                            @csrf
                            @method('PUT')
                            
                            <div>
                                <x-input-label for="value_{{ $setting->setting_key }}" :value=" ucwords(__('settings.common.new_value'))" />
                                <x-form.select-box id="value_{{ $setting->setting_key }}" name="value"  :options="[
                                    ['value' => 'false', 'text' => __('settings.common.disabled'), 'selected' => $setting->setting_value === 'false'],
                                    ['value' => 'true', 'text' => __('settings.common.enabled'), 'selected' => $setting->setting_value === 'true'],
                                ]">
                                </x-form.select-box>
                                <x-input-error :messages="$errors->get($setting->setting_key)" class="mt-2" />
                            </div>

                            <div class="flex justify-end">
                                <x-button type="submit" color_type="purple-600">
                                    <x-slot:icon>
                                        <i class="fas fa-save me-2"></i>
                                    </x-slot:icon>
                                    {{__('settings.common.update')}}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- AI Question Generation Settings Section --}}
    <div class="mb-8 border-t-4 border-orange-500 pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 bg-orange-100 rounded-lg">
                <i class="fas fa-robot text-orange-600 text-xl"></i>
            </div>
            <div class="ml-3 space-y-2">
                <h2 class="text-xl font-semibold text-gray-900">{{__('settings.categories.ai.title')}}</h2>
                <p class="text-sm text-gray-600">{{ __('settings.categories.ai.description') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($aiSettings as $setting)
                <div class="bg-white rounded-lg shadow-md p-2 md:p-6 border border-gray-200">
                    <div class="flex items-center mb-4 gap-2">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <i class="fas fa-robot text-orange-600 text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1 space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __("settings.ai.{$setting->setting_key}.name") }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ __("settings.ai.{$setting->setting_key}.description") }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{__('settings.common.current_value')}}:</span>
                            <span class="font-semibold text-lg text-gray-900">{{ $setting->setting_value }}</span>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p><strong>{{__('settings.common.unit')}}:</strong> {{ __("settings.ai.{$setting->setting_key}.unit") }}</p>
                            <p><strong>{{__('settings.common.help')}}:</strong> {{ __("settings.ai.{$setting->setting_key}.help") }}</p>
                        </div>

                        <form method="POST" action="{{ route('admin.system.settings.update', $setting->setting_key) }}" class="space-y-3">
                            @csrf
                            @method('PUT')

                            <div>
                                <x-input-label for="value_{{ $setting->setting_key }}" :value=" ucwords(__('settings.common.new_value'))" />
                                <x-text-input 
                                        id="value_{{ $setting->setting_key }}" 
                                        type="text" class="mt-1 block w-full" 
                                        :value="$setting->setting_value"
                                        name="value"
                                        />
                                <x-input-error :messages="$errors->get($setting->setting_key)" class="mt-2" />
                            </div>
                            
                            <div class="flex justify-end">
                                <x-button type="submit" color_type="orange">
                                    <x-slot:icon>
                                        <i class="fas fa-save me-2"></i>
                                    </x-slot:icon>
                                    {{__('settings.common.update')}}
                                </x-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($allSettings->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-cogs text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No System Settings Found</h3>
            <p class="text-gray-600">System settings will appear here once they are configured.</p>
        </div>
    @endif
@endsection 