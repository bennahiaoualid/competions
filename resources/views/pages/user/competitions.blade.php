@php use Illuminate\Support\Facades\Auth; @endphp
@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('links.competition.list')}}
    @stop
@endsection


@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-lg" >
        <h1 class="text-xl font-bold">{{__('links.competition.list')}}</h1>
        <div x-data>
            <x-button
                name="myModal"
                x-on:click="$dispatch('open-modal', { detail: 'filter' })">
                <x-slot:icon>
                    <i class="fa-solid fa-filter me-2"></i>
                </x-slot:icon>
                {{__("form.actions.filter")}}
            </x-button>
        </div>
    </div>

    @if($competitions->count() > 0)
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4 max-w-full mt-4">
       @foreach($competitions as $competition)
           <div class="bg-white border shadow-md p-4 space-y-2">

               <p class=" sm:text-lg text-sky-600 font-bold capitalize sm:truncate">{{$competition->title}}</p>

               <p class="text-sm text-gray-400 leading-6 truncate max-w-[40ch]">{{$competition->description}}</p>

               <div class="flex justify-between items-center">
                   <p class="capitalize">
                       <span class="font-bold text-sky-600"> {{__('competition.info.users_age')}} :</span>
                       {{$competition->age_start}}
                       {{__('competition.info.to') . ' ' . $competition->age_end}}

                   </p>
                   <x-status-widget :status="$competition->getStatus()" :outline="false"
                                    :text="__('competition.info.status.' . $competition->getStatus())">
                   </x-status-widget>
               </div>

               <div class="flex justify-between items-center">
                   <p class="py-1 px-2 border border-sky-600 text-sky-600" class="">{{$competition->start_date->inUserTimezone()}}</p>
                   <x-button :islink="true" href="{{route('competitions.detail',['id'=>base64_encode($competition->id)])}}">
                       {{__('messages.global.details')}}
                   </x-button>
               </div>
           </div>
       @endforeach
    </div>
    @else
        <div class="text-center">
            <div class="text-gray-500 mb-4">
                <i class="fas fa-folder-open text-6xl"></i>
            </div>
            <div class="text-gray-700 text-lg font-semibold">
                {{__('messages.global.no_records')}}
            </div>
        </div>
    @endif

    <!-- filter form -->
    <x-modal name="filter" title="My Modal" :show="$errors->hasBag('filterCompetitions')">
        <x-slot:modalhead>
            {{__("form.filter.filter")}}
        </x-slot>
        @php
            $url = 'competitions';
            $user = null;
            if(\Illuminate\Support\Facades\Auth::guard('web')->check() && ! \Illuminate\Support\Facades\Auth::user()->guest){
                $url = 'user.competitions';
                $user = base64_encode(Auth::user()->anonymized_identifier);
            }
        @endphp
        <form id="filter" method="post" action="{{ route($url,['user'=>$user]) }}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <x-input-label for="title" :value=" ucwords(__('competition.info.title'))" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->filterCompetitions->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                <div class="flex flex-col sm:flex-row justify-between">
                    <div>
                        <x-input-label for="start_date_from" :value=" ucwords(__('competition.info.from'))" />
                        <x-text-input id="start_date_from" name="start_date_from" type="text" lang="en"
                                      class="mt-1 block w-full date-input"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="start_date_to" :value=" ucwords(__('competition.info.to'))" />

                        <x-text-input id="start_date_to" name="start_date_to" type="text" lang="en"
                                      class="mt-1 block w-full date-input" />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_end')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="age_start" :value=" ucwords(__('competition.info.users_age'))" />
                <div class="flex flex-col sm:flex-row justify-between">
                    <div>
                        <x-text-input id="age_start" name="age_start" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_start')"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-text-input id="age_end" name="age_end" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_end')"  />
                        <x-input-error :messages="$errors->filterCompetitions->get('age_end')" class="mt-2" />
                    </div>
                </div>
            </div>
            <div>
                <x-input-label for="status" :value=" ucwords(__('competition.info.status.state'))" />
                <x-form.select-box id="status" name="status"  :options="[
                    ['value' => '', 'text' => __('form.filter.all'), 'selected' => true],
                    ['value' => '0', 'text' => __('competition.info.status.inactive'), 'selected' => false],
                    ['value' => '1', 'text' => __('competition.info.status.active'), 'selected' => false],
                    ['value' => '2', 'text' => __('competition.info.status.finished'), 'selected' => false],
                ]">
                </x-form.select-box>
                <x-input-error :messages="$errors->createAdmin->get('gender')" class="mt-2" />
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="filter" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
@endsection
@section('custom_js')
    <script>
        flatpickr(".date-input", {
            enableTime: true,
            dateFormat: "Y-m-d",
            locale: "en"
        });
    </script>
@endsection

