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
     <div class="md:basis-5/12">
         {{-- edit information form--}}
         <div class="w-full shadow-card p-3">
             <!-- section title -->
             <div class="flex justify-between items-center my-2 p-2 shadow-sm" >
                 <h2 class="text-xl font-bold capitalize">{{__('competition.level.info')}}</h2>
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
             <form id="edit_level_form" method="post" action="{{ route('admin.competitions.level.update') }}" class="space-y-2">
                 @csrf
                 @method('patch')
                 <input type="hidden" name="competition_id" value="{{$level->competition_id}}">
                 <input type="hidden" name="id" value="{{$level->id}}">
                 <div>
                     <x-input-label for="name" :value=" ucwords(__('competition.level.name'))" />
                     <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                   :value="old('name', $level->name)" />
                     <x-input-error :messages="$errors->updateLevel->get('name')" class="mt-2" />
                 </div>

                 <div>
                     <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
                     <x-text-area id="description" name="description"  class="mt-1 block w-full">
                         {{old('description', $level->description)}}
                     </x-text-area>
                     <x-input-error :messages="$errors->updateLevel->get('description')" class="mt-2" />
                 </div>

                 <div>
                     <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                     <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full"
                                   :value="old('start_date', $level->start_date->tz(session('timezone')))"/>
                     <x-input-error :messages="$errors->updateLevel->get('start_date')" class="mt-2" />
                 </div>

                 <div>
                     <x-input-label for="questions_number" :value=" ucwords(__('competition.level.questions_number'))" />
                     <x-text-input id="questions_number" type="number" lang="en" class="mt-1 block w-full"
                                   :value="$level->questions_number" disabled/>
                 </div>

                 <div>
                     <x-input-label for="duration" :value=" ucwords(__('competition.level.duration'))" />
                     <x-text-input id="duration" name="duration" type="number" min="1"
                                   lang="en" value="1" class="mt-1 block w-full"
                                   :value="old('duration', $level->duration)" />
                     <x-input-error :messages="$errors->updateLevel->get('duration')" class="mt-2" />
                 </div>

                 <div>
                     <x-input-label for="" :value=" ucwords(__('competition.level.admin'))" />
                     @php
                         $options = [] ;
                     @endphp
                     @foreach($admins as $admin)
                         @php $options[] = ['value' => $admin->id, 'text' => $admin->name, 'selected' => $admin->id == $level->admin_id] @endphp
                     @endforeach
                     <x-form.select-box id="" name="admin_id"  :options="$options">
                     </x-form.select-box>
                     <x-input-error :messages="$errors->updateLevel->get('admin_id')" class="mt-2" />
                 </div>
                 <div class="flex justify-end">
                     @if($level->canEdit())
                         <x-button color_type="success" class="my-1" >{{ __('form.actions.update') }}</x-button>
                     @endif
                 </div>
             </form>
         </div>

     </div>

     {{-- level questions --}}
     <div class="overflow-x-auto max-w-[90vw] p-2 md:basis-6/12 shadow-card">
         <!-- section title -->
         <div class="flex justify-between items-center my-2 p-2 shadow-sm" >
             <h2 class="text-xl font-bold capitalize">{{__('competition.question.list')}}</h2>
             <x-button :islink="true" href='{{route("admin.competitions.level.questions",["id"=>base64_encode($level->id)])}}'>
                 <x-slot:icon>
                     <i class="fa-solid fa-eye me-2"></i>
                 </x-slot:icon>
                 {{__("form.actions.show")}}
             </x-button>
         </div>
     </div>
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
