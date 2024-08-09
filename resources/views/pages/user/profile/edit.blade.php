@extends('layouts.user.master')
@section('css')

    @section('title')
        {{ ucwords(__('user.profile.yours')) }}
    @stop
@endsection


@section('content')

    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ ucwords(__('user.profile.yours')) }}
            </h2>
        </div>
    </header>

    <div class="py-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-2 sm:p-4 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                        @csrf
                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <x-alert
                                outline="true"
                                size="md"
                                :title="__('user.profile.verify.confirm')">
                                <p class="text-sm mt-2 text-gray-800">
                                    {{ __('user.profile.verify.unverified') }}

                                    <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        {{ __('user.profile.verify.re-send-email') }}
                                    </button>
                                </p>
                                @if (session('status') === 'verification-link-sent')
                                    <p class="mt-2 font-medium text-sm text-green-600">
                                        {{ __('user.profile.verify.email-sent') }}
                                    </p>
                                @endif
                            </x-alert>
                        @endif
                    </form>
                </div>
            </div>
            <div class="p-2 sm:p-4 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                     @include('pages.user.profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-2 sm:p-4 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('pages.user.profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    {{-- @include('profile.partials.delete-user-form') --}}
                </div>
            </div>
        </div>
    </div>

@endsection





