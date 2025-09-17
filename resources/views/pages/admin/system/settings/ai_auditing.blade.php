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

    <div class="mb-8 border-t-4 border-blue-600 pt-4 rounded-md">
        <div class="flex items-center mb-4 p-4 gap-2">
            <div class="p-2 bg-blue-500 rounded-lg">
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
                        <div class="p-2 bg-blue-500 rounded-lg">
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
                                @if(isset($fieldMetadata[$setting->setting_key]['is_select']) && $fieldMetadata[$setting->setting_key]['is_select'])
                                    @php
                                        $select_list = $fieldMetadata[$setting->setting_key]['select_list'];
                                        $options = [];
                                        foreach($select_list as $key => $value){
                                            $options[] = ['value' => $key, 'text' => $value, 'selected' => $setting->setting_value == $key];
                                        }
                                        @endphp
                                        <x-form.select-box 
                                            id="value_{{ $setting->setting_key }}" 
                                            :options="$options"
                                            :value="$setting->setting_value"
                                            name="value"
                                        />
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

@section('custom_js')
@php
    $config = [
        'llm_providers' => $llmProviders,
        'model_options' => $modelOptions,
    ];
@endphp
<script id="config" type="application/json">
    @json($config)
</script>
<script>
    const configElement = document.getElementById('config');

    $(document).ready(function(){
        const config = JSON.parse(configElement.textContent);
        $('#value_ai_auditing_llm_provider').change(function(){
            var selectedProvider = $(this).val();
            var modelOptions = config.model_options[selectedProvider];
            $('#value_ai_auditing_model').empty();
            $.each(modelOptions, function(key, value){
                $('#value_ai_auditing_model').append('<option value="' + key + '">' + value + '</option>');
            });
        });
    });
</script>
@endsection