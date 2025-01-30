@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.global.question')}}
    @stop
@endsection


@section('content')
        <div class="max-w-lg mx-auto mt-4 px-2 py-4 border border-primary space-y-6">
            <img class="max-w-16 mx-auto" src="{{asset("assets/images/cry_emoji.png")}}">
            <h1 class="text-2xl font-bold mb-2 text-center text-primary capitalize">
                {{__('competition.question.no_question')}}
            </h1>
            <div class="flex justify-center">
                <x-button class="mx-auto" color_type="primary" :islink="true" href="{{route('user.global_questions.response')}}">
                    {{__('competition.question.try_later')}}
                </x-button>
            </div>
        </div>

@endsection


