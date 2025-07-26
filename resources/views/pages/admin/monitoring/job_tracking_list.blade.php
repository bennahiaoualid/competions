@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.monitoring.job_tracking')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.monitoring.job_list') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.monitoring.job_list')}}</h1>
    </div>

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:job-tracking-table/>
    </div>

    {{-- remove collection of jobs form --}}
    <x-modal name="remove_jobs" title="My Modal" :show="false">
        <x-slot:modalhead>
            {{__("form.job.remove")}}
        </x-slot>
        <form id="remove_jobs" method="post" action="{{route("admin.monitoring.job.delete.bulk")}}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <input type="hidden" name="job_ids" x-model="inputValue"/>
                <p class=""> {{__("form.actions.confirm_job_remove")}}</p>
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="remove_jobs" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
@endsection
