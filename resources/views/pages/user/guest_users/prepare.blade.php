@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.global.question')}}
    @stop
@endsection


@section('content')
    <div class="max-w-lg mx-auto mt-4">
        <h1 class="capitalize text-xl font-bold mb-2 text-center">{{__('competition.global.question')}}</h1>
        <ul class="space-y-2 md:space-y-4">
            <li class="py-2 px-4 rounded-sm shadow-md flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.global.condition.random')}}
            </li>
            <li class="py-2 px-4 rounded-sm shadow-md flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.global.condition.choices_number')}}
            </li>
            <li class="py-2 px-4 rounded-sm shadow-md flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.global.condition.wrong_response')}}
            </li>
            <li class="py-2 px-4 rounded-sm shadow-md flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.global.condition.time')}}
            </li>
            <li class="py-2 px-4 rounded-sm shadow-md flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.global.condition.final_score')}}
            </li>
            @if(\Illuminate\Support\Facades\Auth::guard('web')->check())
                <li class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <!-- Premium Questions Information -->
                    <p class="text-sm text-blue-700 mb-2">
                        💎 {{__('competition.premium.available_info')}}
                    </p>
                    <a href="{{ route('user.global_questions.premium_info') }}" 
                        class="text-xs text-blue-600 hover:text-blue-800 underline">
                        {{__('competition.premium.learn_more')}}
                    </a>
                </li>
            @endif
            <li class="flex justify-center">
                @if(\Illuminate\Support\Facades\Auth::guard('web')->check())
                    <div class="flex gap-2">
                        <x-button color_type="primary"
                                :islink="true" href="{{route('user.global_questions.ai_question_generation')}}">
                            {{__('competition.ai.use_ai')}}
                        </x-button>
                        <x-button form="add-form" color_type="success"
                                :islink="true" href="{{route('user.global_questions.response')}}">
                            {{ __('form.actions.start') }}
                        </x-button>
                    </div>
                    
                @else
                    <x-button color_type="primary"
                            :islink="true" href="{{route('login')}}">
                        {{__('form.actions.login')}}
                    </x-button>
                @endif
            </li>
        </ul>
    </div>



@endsection


