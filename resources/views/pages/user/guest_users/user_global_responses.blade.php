@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('links.global_user.global_responses') }}
    @stop
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="capitalize text-xl font-bold">{{__('links.global_user.global_responses')}}</h1>
    </div>

    <div class="">
        @foreach($questions as $question)
            <x-collapsible-card :title="__('competition.question.the_question').' '.$loop->index+1" type="primary">

                <div dir="{{$question->text_direction}}">
                    <p class="mb-4">
                        {{$question->question_text}}
                        <span class="inline-block py-0.5 px-2 rounded-full bg-primary text-white ">
                            {{$question->score}}
                        </span>
                    </p>

                    @foreach($question->responses as $response)
                        @php
                            $border = $response->score == 0? ' border-danger ':' border-success ';
                            $bg_color = $response->score == 0? ' bg-danger ':' bg-success ';
                        @endphp
                        <p class="{{'flex justify-between mb-2 py-1 px-2 max-w-md rounded-md border '.$border}}">
                           <span>{{$response->choice->choice_text}}</span>
                            <span class="{{'inline-block py-0.5 px-2 rounded-md text-white'.$bg_color}} ">
                                {{$response->score}}
                            </span>
                        </p>
                    @endforeach
                </div>

            </x-collapsible-card>
        @endforeach
        <div class="mt-6">
            {{ $questions->links() }}
        </div>
    </div>

@endsection

