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

<div class="md:flex md:flex-row md:justify-between items-start">
    {{-- main information and levels list --}}
    <div class="md:w-[45%]">
        {{-- edit information form--}}
        <div class="w-full shadow-card p-3">
            <!-- section title -->
            <div class="flex justify-between items-center my-2 p-2 shadow-sm" >
                <h2 class="text-xl font-bold capitalize">{{__('competition.info.information')}}</h2>
                @if($competition->canEdit())
                    {{-- show activation button --}}
                    @if($competition->status == 'pending')
                        <form action="{{route('admin.competitions.activate', ['competition' => $competition])}}" method="post">
                        @csrf
                            @method('post')
                            <x-button color_type="success" type="submit">
                                <x-slot:icon>
                                <i class="fa-solid fa-circle-check fa-fw me-2"></i>
                                </x-slot:icon>
                                {{__("form.actions.activate")}}
                            </x-button>
                        </form>
                    {{-- show finishing button --}}
                    @else
                        <x-status-widget :status="$competition->status" :outline="false"
                                        :text="__('competition.info.status.' . $competition->status)">
                        </x-status-widget>
                    @endif

                @endif
            </div>
            <form id="edit-competition" method="post" action="{{ route('admin.competitions.update', ['competition' => $competition]) }}" class="space-y-2">
                @csrf
                @method('patch')
                <div>
                    <x-input-label for="title" :value=" ucwords(__('competition.info.title'))" />
                    <x-text-input id="title" type="text" class="mt-1 block w-full" :value="$competition->title" readonly />
                </div>

                <div>
                    <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
                    <x-text-area id="description"  class="mt-1 block w-full h-fit"  readonly>
                        {{$competition->description}}
                    </x-text-area>
                </div>

                <div>
                    <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                    <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full"
                                :value="$competition->start_date->inUserTimezone()"/>
                    <x-input-error :messages="$errors->updateCompetition->get('start_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="age_start" :value=" ucwords(__('competition.info.users_age'))" />
                    <div class="flex flex-col sm:flex-row justify-between">
                        <div>
                            <x-text-input id="age_start" name="age_start" type="number" min="6" lang="en"
                                        class="mt-1 block w-full" :placeholder="__('competition.info.age_start')"
                                        :value="$competition->age_start"/>
                            <x-input-error :messages="$errors->UpdateCompetition->get('age_start')" class="mt-2" />
                        </div>
                        <div>
                            <x-text-input id="age_end" name="age_end" type="number" min="6" lang="en"
                                        class="mt-1 block w-full" :placeholder="__('competition.info.age_end')"
                                        :value="$competition->age_end"/>
                            <x-input-error :messages="$errors->updateCompetition->get('age_end')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="levels_number" :value=" ucwords(__('competition.info.levels_number'))" />
                    <x-text-input id="levels_number" type="number" lang="en" class="mt-1 block w-full"
                                :value="$competition->levels_number" readonly />
                </div>

                <div>
                    <x-input-label for="auditing_time_for_level" :value=" ucwords(__('competition.info.auditing_time_for_level'))" />
                    <x-text-input 
                    id="auditing_time_for_level" 
                    name="auditing_time_for_level" 
                    type="number" min="10" 
                    lang="en" class="mt-1 block w-full" 
                    :value="$competition->auditing_time_for_level" />
                    <x-input-error :messages="$errors->updateCompetition->get('auditing_time_for_level')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    @if($competition->canEdit())
                        <x-button color_type="success" class="my-1" >{{ __('form.actions.update') }}</x-button>
                    @endif
                </div>
            </form>
        </div>

    </div>

    {{-- competions details --}}
    <div class="md:w-1/2">

    {{-- competions auditors --}}
    <div class="flex justify-between items-center my-4 p-2 shadow-card" >
        <h2 class="text-xl font-bold capitalize">
            {{__('competition.info.auditor.list')}}
        </h2>

            <x-button :islink="true" href='{{route("admin.competitions.auditors",["id"=>base64_encode($competition->id)])}}'>
                <x-slot:icon>
                    <i class="fa-solid fa-eye me-2"></i>
                </x-slot:icon>
                {{__("form.actions.show")}}
            </x-button>
        </div>

        {{-- competions users --}}
        <div class="flex justify-between items-center my-4 p-2 shadow-card" >
            <h2 class="text-xl font-bold capitalize">
            {{__('competition.info.competitors')}}
            @switch($competition->participants_sync_status)
                @case('in_progress')
                    <span class="text-sm text-warning">Syncing participants...</span>
                    @break
                @case('completed')
                    <span class="text-sm text-success">Participants synced</span>
                    @break
                @case('failed')
                    <span class="text-sm text-danger">Sync failed</span>
                    @break
                @default
                    <span class="text-sm text-primary">Not synced</span>
            @endswitch
        </h2>
        
            <x-button :islink="true" href='{{route("admin.competitions.users",["id"=>base64_encode($competition->id)])}}'>
                <x-slot:icon>
                    <i class="fa-solid fa-eye me-2"></i>
                </x-slot:icon>
                {{__("form.actions.show")}}
            </x-button>
        </div>

        {{-- competition levels --}}
        <div class="overflow-x-auto max-w-[90vw] p-2 shadow-card">
            <!-- section title -->
            <div class="flex justify-between items-center my-2 p-2 shadow-sm" >
                <h2 class="text-xl font-bold capitalize">{{__('competition.level.information')}}</h2>
                @if($competition->canEdit())
                    <div x-data>
                        <x-button
                            name="add_level-"
                            x-on:click="$dispatch('open-modal', { detail: 'add_level' })">
                            <x-slot:icon>
                                <i class="fa-solid fa-plus me-2"></i>
                            </x-slot:icon>
                            {{__("form.actions.add")}}
                        </x-button>
                    </div>
                @endif
            </div>
            @include("pages.admin.competitions.levels_list")
        </div>

    </div>

</div>
{{-- ----------------- forms ----------------- --}}
<!-- add new level form -->
<x-modal name="add_level" title="add_level" :show="$errors->hasBag('createLevel')">
    <x-slot:modalhead>
        {{__("form.level.add")}}
    </x-slot>
    <form id="add_level_form" method="post" action="{{ route('admin.competitions.level.store', ['competition' => $competition]) }}" class="space-y-2">
        @csrf
        @method('post')
        <div>
            <x-input-label for="name" :value=" ucwords(__('competition.level.name'))" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"  />
            <x-input-error :messages="$errors->createLevel->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
            <x-text-area id="description" name="description"  class="mt-1 block w-full"  />
            <x-input-error :messages="$errors->createLevel->get('description')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
            <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full" />
            <x-input-error :messages="$errors->createLevel->get('start_date')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="questions_number" :value=" ucwords(__('competition.level.questions_number'))" />
            <x-text-input id="questions_number" name="questions_number" type="number" min="1"
                        lang="en" value="1" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->createLevel->get('questions_number')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="duration" :value=" ucwords(__('competition.level.duration'))" />
            <x-text-input id="duration" name="duration" type="number" min="1"
                        lang="en" value="1" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->createLevel->get('duration')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="" :value=" ucwords(__('competition.level.admin'))" />
            @php
                $options = [] ;
                foreach ($admins as $admin) {
                    $options[] = ['value' => $admin->id, 'text' => $admin->name, 'selected' => false];
                }
            @endphp
            
            <x-form.searchable-select
                name="admin_id"
                :options="$options"
                :placeholder="__('messages.global.choose')"
                :value="old('admin_id')"
                :disabled="false"
            />
            <x-input-error :messages="$errors->createLevel->get('admin_id')" class="mt-2" />
        </div>
    </form>
    <x-slot:modalfooter>
        <div class="flex justify-end">
            <x-button form="add_level_form" color_type="success" >{{ __('form.actions.save') }}</x-button>
        </div>
    </x-slot>
</x-modal>
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
