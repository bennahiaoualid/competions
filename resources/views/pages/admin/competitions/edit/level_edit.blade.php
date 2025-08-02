@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.level.info')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.competition') . ' ' . $level->competition->title }}
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
                @if($level->canEdit())

                    {{-- show activation button --}}
                    @if($level->status == 'pending')
                        <form action="{{route('admin.competitions.level.activate', ['level' => $level])}}" method="post">
                            @csrf
                            @method('post')
                            <x-button color_type="success">
                                <x-slot:icon>
                                    <i class="fa-solid fa-circle-check fa-fw me-2"></i>
                                </x-slot:icon>
                                {{__("form.actions.activate")}}
                            </x-button>
                        </form>
                    @else
                        {{-- show finishing button --}}
                        @if($level->isStillActive() || $level->status == 'finished')
                            <x-status-widget :status="$level->status" :outline="false"
                                            :text="__('competition.info.status.' . $level->status)">
                            </x-status-widget>
                        @else
                            <form action="{{route('admin.competitions.level.finish', ['level' => $level])}}" method="post">
                                @csrf
                                @method('post')
                                <x-button color_type="danger">
                                    <x-slot:icon>
                                        <i class="fa-solid fa-circle-check fa-fw me-2"></i>
                                    </x-slot:icon>
                                    {{__("form.actions.finish")}}
                                </x-button>
                            </form>
                        @endif
                    @endif

                @endif
            </div>
            
            <form id="edit_level_form" method="post" action="{{ route('admin.competitions.level.update', ['level' => $level]) }}" class="space-y-2">
                @csrf
                @method('patch')
                <div>
                    <x-input-label for="name" :value=" ucwords(__('competition.level.name'))" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="$level->name" />
                    <x-input-error :messages="$errors->updateLevel->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value=" ucwords(__('competition.info.description'))" />
                    <x-text-area id="description" name="description"  class="mt-1 block w-full">
                        {{$level->description}}
                    </x-text-area>
                    <x-input-error :messages="$errors->updateLevel->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="start_date" :value=" ucwords(__('competition.info.start_date'))" />
                    <x-text-input id="start_date" name="start_date" type="text" class="date-input mt-1 block w-full"
                                :value="$level->start_date->tz(session('timezone'))"/>
                    <x-input-error :messages="$errors->updateLevel->get('start_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="questions_number" :value=" ucwords(__('competition.level.questions_number'))" />
                    <x-text-input id="questions_number" type="number" lang="en" class="mt-1 block w-full"
                                :value="$level->questions_number" disabled/>
                </div>

                <div>
                    <x-input-label for="duration" :value=" ucwords(__('competition.level.duration')) .' ( '. __('messages.global.minute').' )'" />
                    <x-text-input id="duration" name="duration" type="number" min="1"
                                lang="en" value="1" class="mt-1 block w-full"
                                :value="$level->duration" />
                    <x-input-error :messages="$errors->updateLevel->get('duration')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="" :value=" ucwords(__('competition.level.admin'))" />
                    @php
                        $options = [] ;
                        foreach ($admins as $admin) {
                            $options[] = ['value' => $admin->id, 'text' => $admin->name];
                        }
                    @endphp
                    
                    <x-form.searchable-select
                        name="admin_id"
                        :options="$options"
                        :placeholder="__('messages.global.choose')"
                        :value="$level->admin_id"
                        :disabled="false"
                    />
                    <x-input-error :messages="$errors->updateLevel->get('admin_id')" class="mt-2" />
                </div>

                @if($level->canEdit() && $level->competition->status == 'active')
                    <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
                        <input type="checkbox" id="only_manager_change" name="only_manager_change" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <label for="only_manager_change" class="text-sm text-gray-700">
                            {{ __('competition.level.only_manager_change') }}
                        </label>
                    </div>
                @endif

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
            <h2 class="text-xl font-bold capitalize">
                {{__('competition.question.list')}}
                <span class="me-2">({{$level->questions->count() .'/'. $level->questions_number}})</span>
            </h2>
            <x-button :islink="true" href='{{route("admin.competitions.level.questions",["level"=>$level])}}'>
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
