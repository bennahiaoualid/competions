@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.competition.competitions')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.dashboard') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.competition.list')}}</h1>
       @can("add competition")
        <div x-data>
            <x-button
                name="myModal"
                x-on:click="$dispatch('open-modal', { detail: 'myModal' })">
                <x-slot:icon>
                    <i class="fa-solid fa-plus me-2"></i>
                </x-slot:icon>
               {{__("form.actions.add")}}
            </x-button>
        </div>
        @endcan
    </div>

    @can("add competition")
    <x-modal name="myModal" title="My Modal" :show="$errors->hasBag('createCompetition')">
        <x-slot:modalhead>
            {{__("form.competition.add")}}
        </x-slot>
        <form id="add-form" method="post" action="{{ route('admin.competitions.store') }}" class="space-y-4">
            @csrf
            @method('post')

            <div>
                <x-input-label for="title" :value=" ucwords(__('competition.info.title'))" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createCompetition->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
                <x-text-area id="description" name="description"  class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createCompetition->get('description')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full" />
                <x-input-error :messages="$errors->createCompetition->get('start_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="age_start" :value=" ucwords(__('competition.info.users_age'))" />
                <div class="flex flex-col sm:flex-row justify-between">
                    <div>
                        <x-text-input id="age_start" name="age_start" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_start')"  />
                        <x-input-error :messages="$errors->createCompetition->get('age_start')" class="mt-2" />
                    </div>
                    <div>
                        <x-text-input id="age_end" name="age_end" type="number" min="6" lang="en"
                                      class="mt-1 block w-full" :placeholder="__('competition.info.age_end')"  />
                        <x-input-error :messages="$errors->createCompetition->get('age_end')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <x-input-label for="levels_number" :value=" ucwords(__('competition.info.levels_number'))" />
                <x-text-input id="levels_number" name="levels_number" type="number" min="1"
                              lang="en" value="1" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->createCompetition->get('levels_number')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="auditing_time_for_level" :value=" ucwords(__('competition.info.auditing_time_for_level'))" />
                <x-text-input id="auditing_time_for_level" name="auditing_time_for_level" type="number" min="10" lang="en" value="10" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->createCompetition->get('auditing_time_for_level')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="winner_gifts" :value=" ucwords(__('competition.info.winner_gifts'))" />
                <x-text-input id="winner_gifts" name="winner_gifts" type="number" min="{{ $competitionGift }}"
                              lang="en" value="{{ $competitionGift }}" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->createCompetition->get('winner_gifts')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <x-toggle-switch 
                        name="multi_winner" 
                        label="{{ __('competition.info.multi_winner') }}" 
                        :checked="old('multi_winner')" />
                    <x-input-error :messages="$errors->createCompetition->get('multi_winner')" class="mt-2" />
                </div>
                <div>
                    <x-toggle-switch 
                        name="ai_auditing" 
                        label="{{ __('competition.info.ai_auditing') }}" 
                        :checked="old('ai_auditing')" />
                    <x-input-error :messages="$errors->createCompetition->get('ai_auditing')" class="mt-2" />
                </div>
            </div>

            <!-- Dynamic Reward Calculation Display -->
            <div id="rewardCalculation" class="hidden bg-gray-50 p-4 rounded-lg border">
                <h4 class="font-medium text-gray-900 mb-3">{{ __('competition.info.reward.calculation') }}</h4>
                
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <span class="text-gray-600">{{ __('competition.info.reward.first_place') }}:</span>
                        <span class="font-medium" id="firstPlaceCoins">0</span>
                        <span class="text-gray-600">{{ __('messages.global.coins') }}</span>
                    </div>
                    
                    <div id="secondPlaceRow" class="flex gap-2">
                        <span class="text-gray-600">{{ __('competition.info.reward.second_place') }} (<span id="secondPlacePercentage">{{ $secondPlacePercentage ?? 50 }}</span>%):</span>
                        <span class="font-medium" id="secondPlaceCoins">0</span>
                        <span class="text-gray-600">{{ __('messages.global.coins') }}</span>
                    </div>
                    
                    <div id="thirdPlaceRow" class="flex gap-2">
                        <span class="text-gray-600">{{ __('competition.info.reward.third_place') }} (<span id="thirdPlacePercentage">{{ $thirdPlacePercentage ?? 20 }}</span>%):</span>
                        <span class="font-medium" id="thirdPlaceCoins">0</span>
                        <span class="text-gray-600">{{ __('messages.global.coins') }}</span>
                    </div>
                    
                    <div class="border-t pt-2 mt-2">
                        <div class="flex gap-2 font-semibold">
                            <span class="text-gray-800">{{ __('competition.info.reward.total') }}:</span>
                            <span class="text-blue-600" id="totalCoins">0</span>
                            <span class="text-gray-600">{{ __('messages.global.coins') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Error Modal -->
            <div id="balanceErrorModal" class="hidden text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                    <i class="fas fa-coins text-2xl text-orange-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-orange-800 mb-4">
                    {{__('competition.ai.insufficient_balance_title')}}
                </h3>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-orange-700">{{__('competition.ai.required_coins')}}:</span>
                        <span id="requiredCoins" class="font-semibold text-orange-800">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-orange-700">{{__('competition.ai.available_coins')}}:</span>
                        <span id="availableCoins" class="font-semibold text-orange-800">0</span>
                    </div>
                </div>
            </div>

        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="add-form" id="submit-add-form-button" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
    @endcan

    @hasanyrole('super_admin|owner')
        <x-modal name="delete" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.competition.delete")}}
            </x-slot>
            <form id="delete-form" method="post" action="{{route("admin.competitions.delete")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="id" x-model="inputValue"/>
                    <x-alert
                        type="danger"
                        outline="true"
                        size="sm"
                        :title="__('messages.alert.type.danger')"
                    >
                        {{__('messages.alert.content.delete_competition')}}
                    </x-alert>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endhasanyrole
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:competition-table/>
    </div>
@endsection
@section('custom_js')
    @php
        $config = [
            'competitionGift' => $competitionGift ?? 500,
            'secondPlacePercentage' => $secondPlacePercentage ?? 50,
            'thirdPlacePercentage' => $thirdPlacePercentage ?? 20,
            'userBalance' => $userBalance ?? 0,
        ];
    @endphp

    <script id="competition-config" type="application/json">
        @json($config)
    </script>
    @vite('resources/js/competition-coins-calculation.js')

    <script>
        flatpickr(".date-input", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "en"
        });
    </script>
@endsection
