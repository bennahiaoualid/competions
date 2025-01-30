@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.question.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.competition') . ' ' . $level->competition->title }}
@endsection

@section('content')
    <div class="fmy-2 p-4 shadow-card" >
        <div class="flex justify-between">
            <h1 class="text-xl font-bold mb-2">
                {{__('competition.question.list')}}
                <span> : {{$level->name}}</span>
            </h1>
            @if($questions->count() == 0)
                <x-button form="add" color_type="success" >{{ __('form.actions.save') }}</x-button>
            @endif
        </div>
        <div class="mt-4">
            @include("pages.admin.competitions.questions_list")
        </div>
    </div>

@endsection

