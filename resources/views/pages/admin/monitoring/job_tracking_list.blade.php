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
@endsection
