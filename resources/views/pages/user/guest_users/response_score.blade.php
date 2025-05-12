@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.global.question')}}
    @stop
@endsection


@section('content')
    @if($data_result['correct'])
        <div class="max-w-lg mx-auto mt-4 px-2 py-4 border border-success space-y-6">
            <img class="max-w-16 mx-auto" src="{{asset("assets/images/win_emoji.png")}}">
            <h1 class="text-2xl font-bold mb-2 text-center text-success capitalize">
                {{__('competition.response.congratulation')}}
            </h1>
            <div class="mx-auto px-8 py-1 rounded-full border border-success max-w-fit text-green-800">
                <span class="text-2xl font-bold">{{$data_result['response']['score']}}</span>
                <sub>/ {{$data_result['question']['score']}}</sub>
            </div>
            <div class="space-y-2">
                <h2 class="w-fit mx-auto px-8 py-1 rounded-full border border-primary text-primary text-xl capitalize">
                    {{__('competition.question.the_question')}}
                </h2>
                <p class="text-center">
                    {{$data_result['question']['question_text']}}
                </p>

                <p class="w-fit mx-auto px-2 py-1 rounded-md text-success border border-success">
                    {{$data_result['choice']}}
                </p>
            </div>
            <div class="flex justify-center">
                <x-button color_type="success" :islink="true" href="{{route('user.global_questions.response')}}">
                    {{__('competition.response.play_more')}}
                </x-button>
            </div>

        </div>
    @else
        <div class="max-w-lg mx-auto mt-4 px-2 py-4 border border-danger space-y-6">
            <img class="max-w-16 mx-auto" src="{{asset("assets/images/cry_emoji.png")}}">
            <h1 class="text-2xl font-bold mb-2 text-center text-danger capitalize">
                {{__('competition.response.try_again')}}
            </h1>
            <div class="mx-auto px-8 py-1 rounded-full border border-danger max-w-fit text-red-700">
                <span class="text-2xl font-bold">{{$data_result['response']['score']}}</span>
                <sub>/ {{$data_result['question']['score']}}</sub>
            </div>
            <div class="space-y-2">
                <h2 class="w-fit mx-auto px-8 py-1 rounded-full border border-primary text-primary text-xl capitalize">
                    {{__('competition.question.the_question')}}
                </h2>
                <p class="text-center">
                    {{$data_result['question']['question_text']}}
                </p>

                <p class="w-fit mx-auto px-2 py-1 rounded-md text-danger border border-danger">
                    {{$data_result['choice']}}
                </p>
            </div>
            <div class="flex justify-center">
                <x-button color_type="danger" :islink="true" href="{{route('user.global_questions.response')}}">
                    {{__('competition.response.try_again')}}
                </x-button>
            </div>
        </div>
    @endif

@endsection


