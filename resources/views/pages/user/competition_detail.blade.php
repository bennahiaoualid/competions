@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.info.competition') . ' | ' . $competition->title}}
    @stop
@endsection


@section('content')

    <div class="flex flex-col sm:flex-row sm:justify-between sm:space-x-4">
        <!-- First Column (45% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[45%] mb-4 sm:mb-0">
            <x-collapsible-card :title="__('competition.info.information')" type="info">
                <div class="space-y-6">
                    <div class="flex justify-between">
                        <h1 class=" sm:text-xl text-sky-600 font-bold capitalize">{{$competition->title}}</h1>
                        <x-status-widget :status="$competition->getStatus()" :outline="false"
                                         :text="__('competition.info.status.' . $competition->getStatus())">
                        </x-status-widget>
                    </div>

                    <p class="text-sm sm:text-base text-gray-500 leading-6 text-wrap">{{$competition->description}}</p>

                    <p class="capitalize">
                        <span class="font-bold text-sky-600"> {{__('competition.info.users_age')}} :</span>
                        {{$competition->age_start}}
                        {{__('competition.info.to') . ' ' . $competition->age_end}}

                    </p>
                    <p class="py-1 px-2 border border-sky-600 text-sky-600 max-w-fit">
                        {{__('competition.info.start_date') . ' : '}}
                        {{$competition->start_date->inUserTimezone()->format('Y-m-d H:i')}}
                    </p>
                </div>
            </x-collapsible-card>

        </div>

        <!-- Second Column (50% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[50%]">
            <!-- Your content for the second column -->
            <x-collapsible-card :title="(__('competition.level.information'))" type="info">
               @include("pages.user.levels_list")
            </x-collapsible-card>
            <!-- users order list -->
            <x-collapsible-card :title="__('competition.result.competition')" type="info">
                @include("pages.user.users_order_list")
                @if($users->count() > 0)
                    <div class="mt-4 flex gap-4 justify-center items-center text-sm">
                        <x-button :islink="true" href="{{route('competitions.order',['id'=>base64_encode($competition->id)])}}">
                            {{__('messages.global.see_all')}}
                        </x-button>
                    </div>
                @endif

            </x-collapsible-card>
        </div>
    </div>


@endsection


