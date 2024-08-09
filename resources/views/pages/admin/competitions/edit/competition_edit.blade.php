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
                 @can("add admin")
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
             <form id="edit-competition" method="post" action="{{ route('admin.competitions.update') }}" class="space-y-2">
                 @csrf
                 @method('patch')
                 <input type="hidden" name="id" value="{{ $competition->id }}">
                 <div>
                     <x-input-label for="title" :value=" ucwords(__('competition.info.title'))" />
                     <x-text-input id="title" type="text" class="mt-1 block w-full" :value="$competition->title" disabled />
                 </div>

                 <div>
                     <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
                     <x-text-area id="description"  class="mt-1 block w-full h-fit" :value="$competition->description" disabled />
                 </div>

                 <div>
                     <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                     <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full"
                                   :value="old('start_date', $competition->start_date->timezone(session()->get('timezone')))"/>
                     <x-input-error :messages="$errors->updateCompetition->get('start_date')" class="mt-2" />
                 </div>

                 <div>
                     <x-input-label for="age_start" :value=" ucwords(__('competition.info.users_age'))" />
                     <div class="flex flex-col sm:flex-row justify-between">
                         <div>
                             <x-text-input id="age_start" name="age_start" type="number" min="6" lang="en"
                                           class="mt-1 block w-full" :placeholder="__('competition.info.age_start')"
                                           :value="old('age_start', $competition->age_start)"/>
                             <x-input-error :messages="$errors->UpdateCompetition->get('age_start')" class="mt-2" />
                         </div>
                         <div>
                             <x-text-input id="age_end" name="age_end" type="number" min="6" lang="en"
                                           class="mt-1 block w-full" :placeholder="__('competition.info.age_end')"
                                           :value="old('age_end', $competition->age_end)"/>
                             <x-input-error :messages="$errors->updateCompetition->get('age_end')" class="mt-2" />
                         </div>
                     </div>
                 </div>

                 <div>
                     <x-input-label for="levels_number" :value=" ucwords(__('competition.info.levels_number'))" />
                     <x-text-input id="levels_number" type="number" lang="en" class="mt-1 block w-full"
                                   :value="$competition->levels_number" disabled />
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

         {{-- competions users --}}
         <div class="flex justify-between items-center my-4 p-2 shadow-card" >
             <h2 class="text-xl font-bold capitalize">{{__('competition.info.competitors')}}</h2>
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
                 @can("add admin")
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
                 @endcan
             </div>
             @include("pages.admin.competitions.levels_list")
         </div>

     </div>


    {{-- <div class="overflow-x-auto max-w-[90vw] p-2 md:basis-6/12 shadow-card">
         <livewire:competition-users-table competition="{{$competition->id}}"/>
     </div> --}}
 </div>
 {{-- ----------------- forms ----------------- --}}
 <!-- add new level form -->
 <x-modal name="add_level" title="add_level" :show="$errors->hasBag('createLevel')">
     <x-slot:modalhead>
         {{__("form.level.add")}}
     </x-slot>
     <form id="add_level_form" method="post" action="{{ route('admin.competitions.level.store') }}" class="space-y-2">
         @csrf
         @method('post')
         <input type="hidden" name="competition_id" value="{{$competition->id}}" >
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
             @endphp
             @foreach($admins as $admin)
                 @php $options[] = ['value' => $admin->id, 'text' => $admin->name, 'selected' => false] @endphp
             @endforeach
             <x-form.select-box id="" name="admin_id"  :options="$options">
             </x-form.select-box>
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
