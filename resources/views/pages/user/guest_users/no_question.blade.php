@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.global.question')}}
    @stop
@endsection


@section('content')
    @php
        if(isset($type) && $type === 'premium'){
            $title = __('competition.question.no_question_premium');
            $button = [
                'text' => __('competition.response.play_more_free'),
                'color_type' => "primary",
                'outline' => false,
                'islink' => true,
                'href' => route('user.global_questions.response'),
            ];
        }else{
            $title = __('competition.question.no_question_free');
            $button = [
                'text' => __('competition.response.play_more_premium'),
                'color_type' => "primary",
                'outline' => false,
                'islink' => true,
                'href' => route('user.global_questions.premium_info'),
            ];
        }
    @endphp
        <div class="max-w-lg mx-auto mt-4 px-2 py-4 border border-primary space-y-6">
            <img class="max-w-16 mx-auto" src="{{asset("assets/images/cry_emoji.png")}}">
            <h1 class="text-2xl font-bold mb-2 text-center text-primary capitalize">
                {{$title}}
            </h1>
            <div class="flex justify-center gap-4">
                <x-button color_type="{{$button['color_type']}}" :outline="$button['outline']" :islink="true" href="{{$button['href']}}">
                    {{$button['text']}}
                </x-button>
                <x-button color_type="primary" :outline="true"
                        :islink="true" href="{{route('user.global_questions.ai_question_generation')}}">
                    {{__('competition.ai.use_ai')}}
                </x-button>
            </div>
        </div>

@endsection


