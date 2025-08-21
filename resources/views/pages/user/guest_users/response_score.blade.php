@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.global.question')}}
    @stop
@endsection


@section('content')
    @php
        if($data_result['correct']){
            $title = __('competition.response.congratulation');
            $emoji = "win_emoji.png";
            $border_color = "border-success";
            $text_color = "text-success";
            $button_free = [
                    'text' => __('competition.response.play_more'),
                    'color_type' => "success",
                    'islink' => true,
                    'outline' => true,
                    'href' => route('user.global_questions.response'),
                ];
            if($type === 'premium'){
                $button_premium = [
                'text' => __('competition.response.play_more_premium'),
                'color_type' => "success",
                'outline' => false,
                'islink' => true,
                'href' => route('user.global_questions.premium.browse'),
                ];
            }elseif($type === 'ai'){
                $button_ai = [
                    'text' => __('competition.response.play_more_ai'),
                    'color_type' => "primary",
                    'outline' => false,
                    'islink' => true,
                    'href' => route('user.global_questions.response.ai'),
                ];
            }else{
                $button_free['text'] = __('competition.response.play_more_free');
                $button_free['outline'] = false;
            }
        }else{
            $title = __('competition.response.try_again');
            $emoji = "cry_emoji.png";
            $border_color = "border-danger";
            $text_color = "text-danger";
            $button_free = [
                'text' => __('competition.response.try_again'),
                'color_type' => "danger",
                'outline' => true,
                'islink' => true,
                'href' => route('user.global_questions.response'),
            ];
            if($type === 'premium'){
                $hint = __('competition.premium.question_bought');
                $button_premium = [
                'text' => __('competition.response.play_more_premium'),
                'color_type' => "danger",
                'outline' => false,
                'islink' => true,
                'href' => route('user.global_questions.premium.browse'),
                ];
            }elseif($type === 'ai'){
                $button_ai = [
                    'text' => __('competition.response.play_more_ai'),
                    'color_type' => "danger",
                    'outline' => false,
                    'islink' => true,
                    'href' => route('user.global_questions.response.ai'),
                ];
            }else{
                $button_free['text'] = __('competition.response.play_more_free');
                $button_free['outline'] = false;
            }
        }
    @endphp
    <div class="max-w-lg mx-auto mt-4 px-2 py-4 border {{$border_color}} space-y-3 md:space-y-6 rounded-md">
        <img class="max-w-16 mx-auto" src="{{asset("assets/images/".$emoji)}}">
        <h1 class="text-2xl font-bold mb-2 text-center {{$text_color}} capitalize">
            {{$title}}
        </h1>
        <div class="mx-auto px-8 py-1 rounded-full border {{$border_color}} max-w-fit {{$text_color}}">
            <span class="text-2xl font-bold">{{$data_result['response']['score']}}</span>
            <sub>/ {{$data_result['question']['score']}}</sub>
        </div>
        <div class="space-y-2">
            <h2 class="w-fit mx-auto px-8 py-1 rounded-full border border-primary text-primary text-xl capitalize">
                {{__('competition.question.the_question')}}
            </h2>
            <p class="text-center leading-6">
                {{$data_result['question']['question_text']}}
            </p>

            <p class="w-fit mx-auto px-2 py-1 rounded-md {{$text_color}} border {{$border_color}}">
                {{$data_result['choice']}}
            </p>

            @if($data_result['show_explanation'])
                <p class="w-fit mx-auto px-8 py-1 rounded-md border {{$border_color}} leading-6">
                    {{$data_result['question']['explanation']}}
                </p>
            @endif
            @if($type === 'premium')
                <x-alert
                    type="info"
                    outline="true"
                    size="md"
                    :closable="true"
                    :title="__('messages.alert.type.info')"
                >
                    {{$hint}}
                </x-alert>
            @endif  
        </div>
        <div class="flex justify-center gap-4">
            <x-button color_type="{{$button_free['color_type']}}" :outline="$button_free['outline']" :islink="true" href="{{$button_free['href']}}">
                {{$button_free['text']}}
            </x-button>
            @if($type === 'premium')
                <x-button color_type="{{$button_premium['color_type']}}" :outline="$button_premium['outline']" :islink="true" href="{{$button_premium['href']}}">
                    {{$button_premium['text']}}
                </x-button>
            @elseif($type === 'ai')
                <x-button color_type="{{$button_ai['color_type']}}" :outline="$button_ai['outline']" :islink="true" href="{{$button_ai['href']}}">
                    {{$button_ai['text']}}
                </x-button>
            @endif
        </div>

    </div>


@endsection


