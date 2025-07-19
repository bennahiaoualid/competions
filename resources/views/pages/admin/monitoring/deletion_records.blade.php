@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.monitoring.deletion_records')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.monitoring.deletion_records') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{__('links.monitoring.deletion_records')}}</h1>
    </div>

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:deletion-records-table/>
    </div>

    {{-- Hard Delete Modal - Only show if user has appropriate permissions --}}
        <x-modal name="hard_delete_modal" title="Hard Delete Confirmation" :show="false">
            <x-slot:modalhead>
                {{__("form.deletion.hard_delete")}}
            </x-slot>
            <form id="hard-delete-form" method="post" action="{{ route('admin.monitoring.deletion-records.hard-delete') }}" class="space-y-2">
                @csrf
                @method('post')
                <div>
                    <input type="hidden" name="deletion_request_id" x-model="inputValue"/>
                    <x-alert type="danger" outline="true" size="sm" :title="__('messages.alert.type.danger')">
                        <p>{{__('messages.alert.content.hard_delete_warning')}}</p>
                        <p class="mt-2"><strong>{{ __('deletion.records.entity') }}:</strong> <span x-text="payload.entityName"></span></p>
                    </x-alert>
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="hard-delete-form" color_type="danger">{{ __('form.actions.hard_delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

    {{-- this modal only for hard delete admin --}}
    @can('hard_delete admin')
        <x-modal name="hard_delete_admin_modal" title="Hard Delete Confirmation" :show="false">
            <x-slot:modalhead>
                {{__("form.deletion.hard_delete")}}
            </x-slot>
            <form id="hard-delete-admin-form" method="post" action="{{ route('admin.monitoring.deletion-records.hard-delete') }}" class="space-y-2">
                @csrf
                @method('post')
                <div>
                    <input type="hidden" name="deletion_request_id" x-model="inputValue"/>
                    
                    <x-alert type="info" outline="true" size="sm" :closable="true" :title="__('messages.alert.type.info')">
                        <p class="mt-2">
                            {{ __('messages.alert.content.hard_delete_admin_alternative') }}
                        </p>
                    </x-alert>

                    <div>
                        <x-input-label for="" :value=" ucwords(__('deletion.records.alternative_admin'))" />
                        @php
                            $options = [] ;
                            foreach ($admins as $admin) {
                                $options[] = ['value' => $admin->id, 'text' => $admin->name, 'selected' => false];
                            }
                        @endphp
                        
                        <x-form.searchable-select
                            name="admin_id"
                            :options="$options"
                            :placeholder="__('messages.global.choose')"
                            :value="old('admin_id')"
                            :disabled="false"
                        />
                        <x-input-error :messages="$errors->hardDeleteAdmin->get('admin_id')" class="mt-2" />
                    </div>
                    
                    <x-alert type="danger" outline="true" size="sm" :title="__('messages.alert.type.danger')">
                        <p>{{__('messages.alert.content.hard_delete_warning')}}</p>
                        <p class="mt-2"><strong>{{ __('deletion.records.entity') }}:</strong> <span x-text="payload.entityName"></span></p>
                    </x-alert>
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="hard-delete-admin-form" color_type="danger">{{ __('form.actions.hard_delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endcan

    {{-- Restore Modal - Only show if user has restore permission --}}
        <x-modal name="restore_modal" title="Restore Confirmation" :show="false">
            <x-slot:modalhead>
                {{__("form.deletion.restore")}}
            </x-slot>
            <form id="restore-form" method="post" action="{{ route('admin.monitoring.deletion-records.restore') }}" class="space-y-2">
                @csrf
                @method('post')
                <div>
                    <input type="hidden" name="deletion_request_id" x-model="inputValue"/>
                    <x-alert type="info" outline="true" size="sm" :title="__('messages.alert.type.info')">
                        <p>{{__('messages.alert.content.restore_confirmation')}}</p>
                        <p class="mt-2"><strong>{{ __('deletion.records.entity') }}:</strong> <span x-text="payload.entityName"></span></p>
                    </x-alert>
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="restore-form" color_type="success">{{ __('form.actions.restore') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
@endsection