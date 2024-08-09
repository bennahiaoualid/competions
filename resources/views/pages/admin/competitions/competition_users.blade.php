@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.question.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.dashboard') }}
@endsection

@section('content')
    <div class="p-2 shadow-card" >
        <h1 class="text-xl text-center md:text-start font-bold mb-2 capitalize">{{__('competition.info.competitors') .' : '. $competition->title}}</h1>
        <div class="overflow-x-auto max-w-[90vw] pt-2 px-2">
            <livewire:competitionusers-table competition="{{$competition->id}}"/>
        </div>
    </div>

    <div class="p-2 shadow-card mt-6" >
        <h2 class="text-xl text-center md:text-start font-bold mb-2 capitalize">{{__('competition.info.competitors_not_in')}}</h2>
        <div class="overflow-x-auto max-w-[90vw] pt-2 px-2">
            <livewire:competition-users-not-participate
                competition_id="{{$competition->id}}"
            ageMin="{{$competition->age_start}}"
            ageMax="{{$competition->age_end}}"
            />
        </div>
    </div>

    <!-- ****************** Forms ***************** -->
    @if($competition->canEdit())
        {{-- add users form --}}
        <x-modal name="add_users" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.competitor.add")}}
            </x-slot>
            <form id="add_users" method="post" action="{{route("admin.competitions.users.store")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="user_ids" x-model="inputValue"/>
                    <input type="hidden" name="competition_id" value="{{$competition->id}}"/>
                    <p class=""> {{__("form.actions.confirm_competitor_add")}}</p>
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="add_users" color_type="success" >{{ __('form.actions.save') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{--delete user form --}}
        <x-modal name="delete" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.competitor.delete")}}
            </x-slot>
            <form id="delete-form" method="post" action="{{route("admin.competitions.users.delete")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="user_id" x-model="inputValue"/>
                    <input type="hidden" name="competition_id" value="{{$competition->id}}"/>
                    <p class=""> {{__("form.actions.confirm_delete")}}</p>
                </div>
                <x-alert
                    type="danger"
                    outline="true"
                    size="sm"
                    :closable="true"
                    :title="__('messages.alert.type.danger')"
                >
                    {{__('messages.alert.content.competition_user_delete')}}
                </x-alert>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endif

@endsection

