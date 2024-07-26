@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.admin.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.admins') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.admin.list')}}</h1>
    </div>



    <div class="overflow-x-auto py-4 px-2">
        <livewire:activity-table/>
    </div>
@endsection
