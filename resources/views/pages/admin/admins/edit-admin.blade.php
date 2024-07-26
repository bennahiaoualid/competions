@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{$user->name}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.admins') }}
@endsection

@section('content')
<div class=" p-4 shadow-md">
    <section class="max-w-xl">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ ucwords($user->name) }}
            </h2>
        </header>

        <form class="mt-6 space-y-6" action="{{route('admin.update')}}" method="post">
            @csrf
            @method("patch")
            <input type="hidden" name="id" value="{{$user->id}}" />
            <div>
                <x-input-label for="name" :value="ucwords(__('user.profile.name'))" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full outline-none" :icon="false" :value="$user->name" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->createAdmin->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="ucwords(__('user.profile.email'))" />
                <x-text-input id="email" name="email" type="text" class="mt-1 block w-full outline-none" :value="$user->email"  autocomplete="username" />
                <x-input-error :messages="$errors->createAdmin->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="add_admin_birthdate" :value=" ucwords(__('user.profile.birthdate'))" />
                <x-text-input id="add_admin_birthdate" name="" type="date" class="mt-1 block w-full" :value="$user->birthdate" disabled />
            </div>

            <div>
                <x-input-label for="add_admin_gender" :value=" ucwords(__('user.profile.genders.gender'))" />
                <x-text-input id="add_admin_gender" name="" type="text" class="mt-1 block w-full outline-none" :value="__('user.profile.genders.'.$user->gender)" disabled />
            </div>

            <div>
                <x-input-label for="" :value=" ucwords(__('roles.role'))" />
                @php
                    $options = [] ;
                    $user_role = $user->roles->first()->name ?? ""
                @endphp
                @foreach($roles as $role)
                    @php $options[] = ['value' => $role->id, 'text' => __('roles.'.$role->name), 'selected' => $user_role == $role->name] @endphp
                @endforeach
                <x-form.select-box id="" name="role"  :options="$options">
                </x-form.select-box>
                <x-input-error :messages="$errors->createAdmin->get('role')" class="mt-2" />
            </div>

            <div>
                <x-button color_type="success" >{{ __('form.actions.update') }}</x-button>
            </div>
        </form>
    </section>
</div>
@endsection
