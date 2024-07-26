@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.admin.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.admins') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="text-xl font-bold">{{__('links.admin.list')}}</h1>
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

    @can("add admin")
    <x-modal name="myModal" title="My Modal" :show="$errors->hasBag('createAdmin')">
        <x-slot:modalhead>
            {{__("form.admin.add")}}
        </x-slot>
        <form id="add-form" method="post" action="{{ route('admin.store') }}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <x-input-label for="add_admin_name" :value=" ucwords(__('user.profile.name'))" />
                <x-text-input id="add_admin_name" name="name" type="text" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createAdmin->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_admin_email" :value=" ucwords(__('user.profile.email'))" />
                <x-text-input id="add_admin_email" name="email" type="email" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createAdmin->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_admin_birthdate" :value=" ucwords(__('user.profile.birthdate'))" />
                <x-text-input id="add_admin_birthdate" name="birthdate" type="date" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createAdmin->get('birthdate')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_admin_gender" :value=" ucwords(__('user.profile.genders.gender'))" />
                <x-form.select-box id="add_admin_gender" name="gender"  :options="[
                    ['value' => 'male', 'text' => __('user.profile.genders.male'), 'selected' => true],
                    ['value' => 'female', 'text' => __('user.profile.genders.female'), 'selected' => false],
                ]">
                </x-form.select-box>
                <x-input-error :messages="$errors->createAdmin->get('gender')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="" :value=" ucwords(__('roles.role'))" />
                @php
                    $options = [] ;
                @endphp
                @foreach($roles as $role)
                    @php $options[] = ['value' => $role->id, 'text' => __('roles.'.$role->name), 'selected' => false] @endphp
                @endforeach
                <x-form.select-box id="" name="role"  :options="$options">
                </x-form.select-box>
                <x-input-error :messages="$errors->createAdmin->get('role')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_admin_password" :value=" ucwords(__('user.profile.password.password'))" />
                <x-text-input id="add_admin_password" name="password" type="password" class="mt-1 block w-full"  />
                <x-input-error :messages="$errors->createAdmin->get('password')" class="mt-2" />
            </div>

            <x-alert
                type="warning"
                outline="true"
                size="md"
                :closable="true"
                :title="__('messages.alert.type.warning')"
            >
                {{__('messages.alert.content.data_cant_change')}}
            </x-alert>

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
    <div class="overflow-x-auto py-4 px-2">
        <livewire:admin-table/>
    </div>
@endsection
