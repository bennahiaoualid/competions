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
        <form id="add-form" method="post" action="{{ route('admin.competitions.store') }}" class="space-y-2">
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
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="add-form" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
    @endcan

    @can("delete admin")
        <x-modal name="delete" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.admin.delete")}}
            </x-slot>
            <form id="delete-form" method="post" action="{{route("admin.delete")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="id" x-model="inputValue"/>
                    <p class=""> {{__("form.actions.confirm_delete")}}</p>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endcan
    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:competition-table/>
    </div>
@endsection
@section('custom_js')
    <script>
        flatpickr(".date-input", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "en"
        });
    </script>
@endsection
