@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.info.auditor.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.dashboard') }}
@endsection

@section('content')
    <div class="p-2 shadow-card" >
        <h1 class="sm:text-lg text-center md:text-start font-bold mb-2 capitalize">{{__('competition.info.auditor.information') .' : '. $competition->title}}</h1>
        <div class="overflow-x-auto max-w-[90vw] pt-2 px-2">
            <livewire:competition-auditors-table :competition="$competition"/>
        </div>
    </div>

   <div class="p-2 shadow-card mt-8" >
        <h2 class="sm:text-lg text-center md:text-start font-bold mb-2 capitalize">{{__('competition.info.auditor.not_in')}}</h2>
        <div class="overflow-x-auto max-w-[90vw] pt-2 px-2">
            <livewire:competition-admin-not-audit :competition="$competition"/>
        </div>
    </div>

    <!-- ****************** Forms ***************** -->
    @if($competition->canEdit())
        {{-- add users form --}}
        <x-modal name="add_auditors" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.auditor.add")}}
            </x-slot>
            <form id="add_auditors" method="post" action="{{route("admin.competitions.auditor.store")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="auditor_ids" x-model="inputValue"/>
                    <input type="hidden" name="competition_id" value="{{$competition->id}}"/>
                    <p class=""> {{__("form.actions.confirm_auditor_add")}}</p>
                </div>
            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="add_auditors" color_type="success" >{{ __('form.actions.save') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

        {{--delete user form --}}
        <x-modal name="delete" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.auditor.delete")}}
            </x-slot>
            <form id="delete-form" method="post" action="{{route("admin.competitions.auditor.delete")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="auditor_id" x-model="inputValue"/>
                    <input type="hidden" name="competition_id" value="{{$competition->id}}"/>
                    <p class=""> {{__("form.actions.confirm_delete")}}</p>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endif

@endsection

