@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{ __('payment.audit.page_title') }}
    @stop
@endsection

@section('page_title')
    {{ __('payment.audit.page_title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">{{ __('payment.audit.page_title') }}</h1>
    </div>

    {{-- Delete Audit Log Modal (disabled: will flash warning) --}}
    @role('owner')
        <x-modal name="delete-audit-log-modal" title="{{ __('payment.audit.actions.delete') }}" :show="$errors->hasBag('deleteAuditLog')">
            <x-slot:modalhead>
                {{ __('payment.audit.actions.delete') }}
            </x-slot>
            <form id="delete-audit-log-form" method="post" action="{{ route('admin.payment.audit_logs.delete') }}" class="space-y-2">
                @csrf
                @method('post')
                <input type="hidden" name="audit_log_id" x-model="inputValue" />
                <p class="my-1">
                    {{ __('payment.audit.messages.delete_confirmation') }}
                </p>
                <p class="text-sm text-warning-600">
                    {{ __('payment.audit.messages.deletion_disabled_warning') }}
                </p>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-audit-log-form" color_type="danger">{{ __('payment.audit.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endrole

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:payment-audit-log-table />
    </div>
@endsection 