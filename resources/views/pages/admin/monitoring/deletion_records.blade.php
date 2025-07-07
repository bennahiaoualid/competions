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

@section('custom_js')
<script>
    // Listen for modal open events from PowerGrid
    document.addEventListener('openHardDeleteModal', function(event) {
        const deletionRequestId = event.detail.id;
        const entityName = event.detail.entityName || 'Unknown';
        const entityType = event.detail.entityType || 'Unknown';
        
        // Set form action
        document.getElementById('hard-delete-form').action = `/admin/monitoring/deletion-records/${deletionRequestId}/hard-delete`;
        
        // Set values for display
        document.querySelector('[name="deletion_request_id"]').value = deletionRequestId;
        document.querySelector('#hard_delete_modal [x-text="entityName"]').textContent = entityName;
        document.querySelector('#hard_delete_modal [x-text="entityType"]').textContent = entityType;
        
        // Open modal
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'hard_delete_modal' }));
    });

    document.addEventListener('openRestoreModal', function(event) {
        const deletionRequestId = event.detail.id;
        const entityName = event.detail.entityName || 'Unknown';
        const entityType = event.detail.entityType || 'Unknown';
        
        // Set form action
        document.getElementById('restore-form').action = `/admin/monitoring/deletion-records/${deletionRequestId}/restore`;
        
        // Set values for display
        document.querySelector('[name="deletion_request_id"]').value = deletionRequestId;
        document.querySelector('#restore_modal [x-text="entityName"]').textContent = entityName;
        document.querySelector('#restore_modal [x-text="entityType"]').textContent = entityType;
        
        // Open modal
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'restore_modal' }));
    });
</script>
@endsection 