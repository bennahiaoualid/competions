@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.question.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.dashboard') }}
@endsection

@section('content')
    <div class="fmy-2 p-4 shadow-card" >
        <div class="flex justify-between">
            <h1 class="text-xl font-bold mb-2">{{__('competition.question.list')}}</h1>
            @if($questions->count() == 0)
                <x-button form="add" color_type="success" >{{ __('form.actions.save') }}</x-button>
            @endif
        </div>
        <div>
            @include("pages.admin.competitions.questions_list")
        </div>
    </div>

@endsection

