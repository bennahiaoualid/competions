@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('messages.global.site_name')}}
    @stop
@endsection


@section('content')
    <div class="md:flex min-h-[clac(100vh - 70px] items-center">
        <div class="flex-1 order-2">
            <img class="max-w-56 md:max-w-96 mx-auto" src="{{asset('assets/images/idea_hero.png')}}">
        </div>
        <div class="flex-1 order-1 mt-4 md:mt-0">
            <div class="max-w-md text-center md:text-start space-y-6">
                <h1 class="text-4xl font-bold uppercase text-primary">{{__('messages.global.site_name')}}</h1>
                <p class="leading-8">
                    {{__('messages.global.site_brief')}}
                </p>
                <div class="flex items-center gap-4 justify-center md:justify-start">
                    <x-button :islink="true" href="{{route('competitions')}}">
                        {{__('messages.global.details')}}
                    </x-button>
                    <x-button :islink="true" href="{{route('login')}}">
                        {{__('form.actions.login')}}
                    </x-button>
                </div>
            </div>

        </div>
    </div>
@endsection


