@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.user.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.user.list') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.user.list')}}</h1>
       @can("add user")
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

    @can("add user")
    <x-modal name="myModal" title="My Modal" :show="$errors->hasBag('createUser')">
        <x-slot:modalhead>
            {{__("form.user.add")}}
        </x-slot>
        <form id="add-form" method="post" action="{{ route('admin.users.store') }}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <x-input-label for="add_user_name" :value=" ucwords(__('user.profile.name'))" />
                <x-text-input id="add_user_name" name="name" type="text" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createUser->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_user_email" :value=" ucwords(__('user.profile.email'))" />
                <x-text-input id="add_user_email" name="email" type="email" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createUser->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_user_birthdate" :value=" ucwords(__('user.profile.birthdate'))" />
                <x-text-input id="add_user_birthdate" name="birthdate" type="date" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createUser->get('birthdate')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_user_gender" :value=" ucwords(__('user.profile.genders.gender'))" />
                <x-form.select-box id="add_user_gender" name="gender"  :options="[
                    ['value' => 'male', 'text' => __('user.profile.genders.male'), 'selected' => true],
                    ['value' => 'female', 'text' => __('user.profile.genders.female'), 'selected' => false],
                ]">

                </x-form.select-box>
                <x-input-error :messages="$errors->createUser->get('gender')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_user_password" :value=" ucwords(__('user.profile.password.password'))" />
                <x-text-input id="add_user_password" name="password" type="password" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createUser->get('password')" class="mt-2" />
            </div>
        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="add-form" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>
    @endcan

    <x-modal name="delete" title="My Modal" :show="false">
        <x-slot:modalhead>
            {{__("form.user.delete")}}
        </x-slot>
        <form id="delete-form" method="post" action="{{route("admin.users.delete")}}" class="space-y-2">
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

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:user-table/>
    </div>
@endsection
