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
                    <div>
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
                            @elseif ($level->finish_job_running)
                                <x-status-widget status="processing" :outline="false"
                                                :text="__('competition.info.status.finish_processing')">
                                </x-status-widget>
                            @elseif($cost['total'] <= $cost['user_balnce'])
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
                    </div>
                @endif
            </div>
            
            <form id="update_form" method="post" action="{{ route('admin.competitions.level.update', ['level' => $level]) }}" class="space-y-2">
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

                {{-- show cost if this level belong to compeition with ai audting option --}}
                @if(!$level->isStillActive() && $level->status == 'active' && $ai_auditing)
                    {{-- not enough balance --}}
                    @if ($cost['total'] > $cost['user_balnce'])
                        <div id="balanceErrorModal" class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                                <i class="fas fa-coins text-2xl text-orange-600"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-orange-800 mb-4">
                                {{__('competition.ai.insufficient_balance_title')}}
                            </h3>

                            <p class="my-2">
                                {{__('competition.ai.insufficient_balance_level_finish')}}
                            </p>
                            
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-orange-700">{{__('competition.ai.required_coins')}}:</span>
                                    <span id="requiredCoins" class="font-semibold text-orange-800">
                                        {{ $cost['total'] }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-orange-700">{{__('competition.ai.available_coins')}}:</span>
                                    <span id="availableCoins" class="font-semibold text-orange-800">
                                        {{ $cost['user_balnce'] }}
                                    </span>
                                </div>
                            </div>
                        </div>  
                    @else
                        <div id="balanceCost" class="text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                                <i class="fas fa-coins text-2xl text-green-600"></i>
                            </div>

                            <h3 class="text-lg font-semibold text-green-800 mb-4">
                                {{__('competition.ai.ai_audting_cost_for_level')}}
                            </h3>

                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-green-700">{{__('competition.ai.required_coins')}}:</span>
                                    <span id="requiredCoins" class="font-semibold text-green-800">
                                        {{ $cost['total'] .' '. __('messages.global.coin')}}
                                    </span>
                                </div>
                            </div>
                        </div>                    
                    @endif

                @endif

                {{-- show how many responses needs audit or confirmation if the level finihed --}}
                @if ($level->canEdit() && $level->status == "finished")
                    <ul class="my-2 p-2 space-y-4 shadow-md">
                        <li class="flex gap-4">
                            <p> <i class="fa-solid fa-square-check me-2 text-success"></i>
                                {{ __('competition.response.response_count.audited') }} :
                            </p> 
                            <span class="text-success">{{ $response_counts['audited'] }}</span>
                        </li>
                        <li class="flex gap-4">
                            <p> <i class="fa-solid fa-circle-xmark me-2 text-danger"></i>
                                {{ __('competition.response.response_count.not_audited') }} :
                            </p> 
                            <span class="text-danger">{{ $response_counts['not_audited'] }}</span>
                        </li>
                        @if ($ai_auditing)
                            <li class="flex gap-4">
                                <p> <i class="fa-solid fa-check-double me-2 text-success"></i>
                                    {{ __('competition.response.response_count.confirmed') }} :
                                </p> 
                                <span class="text-success">{{ $response_counts['confirmed'] }}</span>
                            </li>
                            <li class="flex gap-4">
                                <p> <i class="fa-solid fa-xmark me-2 text-warning"></i>
                                    {{ __('competition.response.response_count.not_confirmed') }} :
                                </p> 
                                <span class="text-warning">{{ $response_counts['not_confirmed'] }}</span>
                            </li>
                        @endif
                    </ul>
                @endif
                <div class="flex justify-end items-center gap-4">
                    @if($level->canEdit())
                        @if ($level->status == "finished" && $auto_audit_pass)
                            @if ($response_counts['not_confirmed'] > 0)
                                <x-button color_type="warning" form="auto-audit" type="submit">
                                    <x-slot:icon>
                                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                                    </x-slot:icon>
                                    {{__("form.actions.auto_audit")}}
                                </x-button>
                            @elseif($response_counts['not_audited'] > 0)
                                <x-button color_type="warning" form="re-assing-users-responses" type="submit"
                                        :tooltip="__('form.actions.reassign_level_auditor.tooltip')">
                                    <x-slot:icon>
                                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                                    </x-slot:icon>
                                    {{__("form.actions.reassign_level_auditor.title")}}
                                </x-button>
                            @endif
                        @endif
                        <x-button form="update_form" color_type="success" class="my-1" :disabled="$level->status != 'pending'" >{{ __('form.actions.update') }}</x-button>
                    @endif
                </div>
            </form>

            {{-- auto edit form --}}
            <form class="hidden" id="auto-audit" action="{{route('admin.auditor.auto_audit', ['level' => $level])}}" method="post">
                @csrf
                @method('post')
            </form>

            {{-- re assign users responses that not audited to creator form --}}
            <form class="hidden" id="re-assing-users-responses" action="{{route('admin.competitions.level.re-assign-user-responses', ['level' => $level])}}" method="post">
                @csrf
                @method('post')
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
