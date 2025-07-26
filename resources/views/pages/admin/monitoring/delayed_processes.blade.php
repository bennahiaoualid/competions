@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('delayed_process.messages.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('delayed_process.messages.title') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('delayed_process.messages.list')}}</h1>
    </div>

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:delayed-process-table/>
    </div>

    {{-- delete odale --}}
    <x-modal name="delete_delayed_record_modal" title="Delete Confirmation" :show="false">
        <x-slot:modalhead>
            {{__("form.process_delayed.delete")}}
        </x-slot>
        <form id="hard-delete-form" method="post" action="{{ route('admin.monitoring.delayed-processes.delete') }}" class="space-y-2">
            @csrf
            @method('DELETE')
            <div>
                <input type="hidden" name="id" x-model="inputValue"/>
                <x-alert type="danger" outline="true" size="sm" :title="__('messages.alert.type.danger')">
                    <p>{{__("form.actions.confirm_delete")}}</p>
                </x-alert>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="hard-delete-form" color_type="danger">{{ __('form.actions.hard_delete') }}</x-button>
            </div>
        </x-slot>
    </x-modal>

@endsection 