@extends('layouts.user.master')

@section('title')
    {{__('competition.premium.title')}}
@stop

@section('content')
    <div class="max-w-2xl mx-auto mt-8">
        <!-- Header -->
        <div class="text-center mb-4 md:mb-6">
            <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-2">{{__('competition.premium.title')}}</h1>
            <p class="text-sm md:text-base text-gray-600 leading-6">{{__('competition.premium.subtitle')}}</p>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-md py-2 px-4 space-y-6 md:space-y-8">
            
            <!-- What Are Premium Questions -->
            <div class="text-center md:text-start">
                <h2 class="text-lg md:text-xl font-semibold text-gray-800 mb-3">{{__('competition.premium.what_are')}}</h2>
                <p class="text-sm md:text-base text-gray-700 leading-6">{{__('competition.premium.definition')}}</p>
            </div>

            <!-- Cost -->
            <div>
                <h2 class="text-lg md:text-xl text-center md:text-start font-semibold text-gray-800 mb-3">{{__('competition.premium.cost')}}</h2>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-base md:text-lg font-semibold text-blue-800">
                        {{__('competition.premium.price')}}: <span class="text-xl md:text-2xl">{{ $premium_cost }}</span> {{__('messages.global.coin')}}
                    </p>
                    <p class="text-blue-600 text-xs md:text-sm mt-1">{{__('competition.premium.cost_note')}}</p>
                </div>
            </div>

            <!-- What You Get -->
            <div>
                <h2 class="text-lg md:text-xl text-center md:text-start font-semibold text-gray-800 mb-3">{{__('competition.premium.what_you_get')}}</h2>
                <ul class="space-y-2 text-sm md:text-base">
                    <li class="flex items-start">
                        <span class="text-green-500 me-2">✓</span>
                        <span class="text-gray-700 leading-6">{{__('competition.premium.benefit_1')}}</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 me-2">✓</span>
                        <span class="text-gray-700">{{__('competition.premium.benefit_2')}}</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 me-2">✓</span>
                        <span class="text-gray-700">{{__('competition.premium.benefit_3')}}</span>
                    </li>
                </ul>
            </div>

            <!-- How It Works -->
            <div>
                <h2 class="text-lg md:text-xl text-center md:text-start font-semibold text-gray-800 mb-3">{{__('competition.premium.how_it_works')}}</h2>
                <ol class="list-decimal list-inside space-y-2 text-sm md:text-base text-gray-700">
                    <li>{{__('competition.premium.step_1')}}</li>
                    <li>{{__('competition.premium.step_2')}}</li>
                    <li>{{__('competition.premium.step_3')}}</li>
                </ol>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center gap-4 mt-8">
            <x-button 
                color_type="primary"
                :islink="true" 
                href="{{ route('user.global_questions.premium.browse') }}"
            >
                {{__('competition.premium.browse_questions')}}
            </x-button>
            
            <x-button 
                color_type="secondary"
                :islink="true" 
                href="{{ route('global_questions.index') }}"
            >
                {{__('form.actions.back')}}
            </x-button>
        </div>
    </div>
@endsection 