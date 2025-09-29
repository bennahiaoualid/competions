@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.level.info')}}
    @stop
@endsection


@section('content')

    <div class="flex flex-col sm:flex-row sm:justify-between sm:space-x-4">
        <!-- First Column (45% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[45%] mb-4 sm:mb-0">
            <x-collapsible-card :title="__('competition.level.info')" type="info">
                <div class="space-y-6">
                    <div class="flex justify-between">
                        <h2 class=" sm:text-xl text-sky-600 font-bold capitalize">{{$level->name}}</h2>
                        <x-status-widget :status="$level->status" :outline="false"
                                        :text="__('competition.info.status.' . $level->status)">
                        </x-status-widget>
                    </div>

                    <p class="text-sm sm:text-base text-gray-500 leading-6 text-wrap">{{$level->description}}</p>

                    <p class="capitalize">
                        <span class="font-bold text-sky-600"> {{__('competition.level.questions_number')}} :</span>
                        {{$level->questions_number}}
                    </p>
                    <p class="capitalize">
                        <span class="font-bold text-sky-600"> {{__('competition.level.duration')}} :</span>
                        {{$level->duration}} {{__('messages.global.minute')}}
                    </p>
                    <p class="py-1 px-2 border border-sky-600 text-sky-600 max-w-fit">
                        {{__('competition.info.start_date') . ' : '}}
                        {{$level->start_date->inUserTimezone()->format('Y-m-d H:i')}}
                    </p>
                </div>
            </x-collapsible-card>

        </div>

        <!-- Second Column (50% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[50%] space-y-4">
            <!--  content for the second column -->
            <!-- competitors response and question answers section -->
            @auth('web')
                {{-- show question list and start solve button --}}
                <div x-data="{}" class="p-4 shadow-xl space-y-3 border border-gray-300 rounded-sm">
                    <div class="flex justify-between">
                        <h3 class="text-lg md:text-xl  text-primary font-bold capitalize">
                            {{ __('competition.question.list') }}
                        </h3>

                        <x-button
                            color_type="warning"
                            class="rounded-xl"
                            x-on:click="$dispatch('open-modal', { detail: 'rules_modal' })">
                            <x-slot:icon>
                                <i class="fa-solid fa-pen-ruler me-2"></i>
                            </x-slot:icon>
                            {{__("competition.response.rules.title")}}
                        </x-button>
                    </div>
                    @if($userCanParticipate)
                        {{-- show start solve button if the level is still active --}}
                        @if($level->isStillActive())
                            <div class="flex justify-between items-center my-4 p-2 border border-primary rounded" >
                                <p class="text-lg font-bold capitalize text-primary">{{__('competition.question.start_solve')}}</p>
                                <x-button :islink="true" href='{{route("user.competitions.level.response",["level"=>$level])}}'>
                                    <x-slot:icon>
                                        <i class="fa-solid fa-eye me-2"></i>
                                    </x-slot:icon>
                                    {{__("form.actions.show")}}
                                </x-button>
                            </div>
                        @else
                            @if($level->status == 'pending')
                                <x-alert
                                    type="info"
                                    outline="true"
                                    size="sm"
                                    :title="__('messages.alert.type.info')"
                                >
                                    {{__('messages.alert.content.response_early')}}
                                </x-alert>
                            @else
                                <x-alert
                                    type="info"
                                    outline="true"
                                    size="sm"
                                    :title="__('messages.alert.type.info')"
                                >
                                    {{__('messages.alert.content.time_response_end')}}
                                </x-alert>
                            @endif
                        @endif
                    @else
                        <x-alert
                            type="info"
                            outline="true"
                            size="sm"
                            :title="__('messages.alert.type.info')"
                        >
                            {{__('messages.alert.content.user_not_part_of_competition')}}
                        </x-alert>
                    @endif
                </div>

                {{-- show competitor responses list --}}
                @if($userCanParticipate && $level->status != 'pending' && !$level->isStillActive() )
                    <x-collapsible-card :title="__('competition.response.info')" type="info">
                        <div class="flex justify-between items-center my-4 p-2 border border-primary rounded" >
                            <p class="text-lg font-bold capitalize text-primary">{{__('competition.response.response')}}</p>
                            <x-button :islink="true" href='{{route("user.competitions.response",["level"=>$level])}}'>
                                <x-slot:icon>
                                    <i class="fa-solid fa-eye me-2"></i>
                                </x-slot:icon>
                                {{__("form.actions.show")}}
                            </x-button>
                        </div>
                    </x-collapsible-card>
                @endif

                
                <!-- users order list -->
                <div class="p-4 shadow-xl space-y-3 border border-gray-300 rounded-sm">
                    <div class="flex justify-between">
                        <h3 class="text-lg md:text-xl  text-primary font-bold capitalize">
                            {{ __('competition.result.level') }}
                        </h3>

                        @if(!$audit_finish)
                            <span class="px-2 py-1 bg-primary rounded-xl text-white text-nowrap">
                                {{__('competition.result.temp')}}
                            </span>
                        @endif
                    </div>
                    @include("pages.user.users_order_list")
                    <div class="mt-4 flex gap-4 justify-center items-center text-sm">
                        <x-button :islink="true" href="{{route('competitions.level.order',['level'=>$level])}}">
                            {{__('messages.global.see_all')}}
                        </x-button>
                    </div>
                </div>
                
            @endauth
        </div>
    </div>

    <x-modal name="rules_modal" title="rules modal" :show="false">
        <x-slot:modalhead>
            {{__("competition.response.rules.title")}}
        </x-slot>
        <ul class="space-y-2 md:space-y-6">
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.close_browser')}}
            </li>
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.switch_tab')}}
            </li>
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.copy_paste')}}
            </li>
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.low_keystrokes')}}
            </li>
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.suspicious_wpm')}}
            </li>
            <li class="py-1 px-2 rounded-sm shadow-sm flex gap-4 items-center">
                <i class="fa-regular fa-circle-check"></i>
                {{__('competition.response.rules.too_fast_long_answer')}}
            </li>
        </ul>
    </x-modal>
@endsection


