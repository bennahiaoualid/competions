@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.competition.auditing_responses')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.competition') . ' : ' . $level->competition->title }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-xl font-bold">
            {{ __('competition.level.level') . ' : ' . $level->name }}
        </h1>
    </div>
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:user-response-auditing-table :admin_id="$admin_id" :level="$level" />
    </div>
@endsection
