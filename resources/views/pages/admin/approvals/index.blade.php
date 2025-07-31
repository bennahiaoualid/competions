@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('admin.admin_approval.title')}}
    @stop
@endsection

@section('page_title')
    {{ __('admin.admin_approval.title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{__('admin.admin_approval.title')}}</h1>
    </div>

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:admin-approval-table/>
    </div>

    {{-- Approve Modal --}}
    <x-modal name="approve-approval-modal" title="Approve Request" :show="false">
        <x-slot:modalhead>
            {{__("admin.admin_approval.actions.approve")}}
        </x-slot>
        <form id="approve-form" method="post" action="{{ route('admin.approvals.approve') }}" class="space-y-2">
            @csrf
            <div>
                <input type="hidden" name="approval_id" x-model="inputValue"/>
                <x-alert type="info" outline="true" size="sm" :title="__('messages.alert.type.info')">
                    <p>{{__('admin.admin_approval.messages.approve_confirmation')}}</p>
                </x-alert>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="approve-form" color_type="success">{{ __('admin.admin_approval.actions.approve') }}</x-button>
            </div>
        </x-slot>
    </x-modal>

    {{-- Reject Modal --}}
    <x-modal name="reject-approval-modal" title="Reject Request" :show="false">
        <x-slot:modalhead>
            {{__("admin.admin_approval.actions.reject")}}
        </x-slot>
        <form id="reject-form" method="post" action="{{ route('admin.approvals.reject') }}" class="space-y-2">
            @csrf
            <div>
                <input type="hidden" name="approval_id" x-model="inputValue"/>
                <x-alert type="warning" outline="true" size="sm" :title="__('messages.alert.type.warning')">
                    <p>{{__('admin.admin_approval.messages.reject_confirmation')}}</p>
                </x-alert>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="reject-form" color_type="danger">{{ __('admin.admin_approval.actions.reject') }}</x-button>
            </div>
        </x-slot>
    </x-modal>

    {{-- Delete Modal --}}
    <x-modal name="delete-approval-modal" title="Delete Request" :show="false">
        <x-slot:modalhead>
            {{__("form.actions.delete")}}
        </x-slot>
        <form id="delete-form" method="post" action="{{ route('admin.approvals.destroy') }}" class="space-y-2">
            @csrf
            @method('DELETE')
            <div>
                <input type="hidden" name="approval_id" x-model="inputValue"/>
                <x-alert type="danger" outline="true" size="sm" :title="__('messages.alert.type.danger')">
                    <p>{{__('admin.admin_approval.messages.delete_confirmation')}}</p>
                </x-alert>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="delete-form" color_type="danger">{{ __('form.actions.delete') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
@endsection 