@extends('layouts.admin.master')

@section('title')
    {{__('settings.title')}}
@endsection

@section('page_title')
    {{ __('settings.title') }}
@endsection

@section('content')
    @php
        switch ($category) {
            case 'payment':
                $text_color = 'text-primary';
                $bg_color = 'bg-primary';
                $border_color = 'border-primary';
                break;
            
            case 'system':
                $text_color = 'text-success';
                $bg_color = 'bg-success';
                $border_color = 'border-success';
                break;
            
            case 'notifications':
                $text_color = 'text-orange-600';
                $bg_color = 'bg-orange-500';
                $border_color = 'border-orange-600';
                break;
            
            case 'service_global_question':
                $text_color = 'text-purple-600';
                $bg_color = 'bg-purple-500';
                $border_color = 'border-purple-600';
                break;
            
            case 'service_ai_auditing':
                $text_color = 'text-blue-600';
                $bg_color = 'bg-blue-500';
                $border_color = 'border-blue-600';
                break;

            default:
                $text_color = 'text-gray-600';
                $bg_color = 'bg-gray-100';
                $border_color = 'border-gray-200';
                break;
        }
    @endphp
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

    <div class="mb-8 border-t-4 {{ $border_color }} pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 {{ $bg_color }} rounded-lg">
                <i class="fas fa-credit-card text-white text-xl"></i>
            </div>
            <div class="ml-3 space-y-2">
                <h2 class="text-xl font-semibold text-gray-900">{{__('settings.categories.' . $category . '.title')}}</h2>
                <p class="text-sm text-gray-600">{{ __('settings.categories.' . $category . '.description') }}</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($settings as $setting)
                <div class="bg-white rounded-lg shadow-md p-2 md:p-6 border border-gray-200">
                    <div class="flex items-center mb-4 gap-2">
                        <div class="p-2 {{ $bg_color }} rounded-lg">
                            <i class="fas fa-credit-card  text-white text-xl"></i>
                        </div>
                        <div class="ml-3 flex-1 space-y-2">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __("{$setting->setting_trans_key}.name") }}
                            </h3>
                            <p class="text-sm text-gray-600">
                                {{ __("{$setting->setting_trans_key}.description") }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium text-gray-700">{{__('settings.common.current_value')}}:</span>
                            <span class="font-semibold text-lg text-gray-900">{{ $setting->setting_value }}</span>
                        </div>

                        <div class="text-sm text-gray-600">
                            <p><strong>{{__('settings.common.unit')}}:</strong> {{ __("{$setting->setting_trans_key}.unit") }}</p>
                            <p><strong>{{__('settings.common.help')}}:</strong> {{ __("{$setting->setting_trans_key}.help") }}</p>
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


    @if($settings->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-cogs text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No System Settings Found</h3>
            <p class="text-gray-600">System settings will appear here once they are configured.</p>
        </div>
    @endif
@endsection 