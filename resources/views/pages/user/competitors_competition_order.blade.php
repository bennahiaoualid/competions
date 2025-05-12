@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('competition.result.competition') }}
    @stop
@endsection


@section('content')
    <div class=" my-2 p-4 shadow-lg" >
        <h1 class="capitalize text-xl font-bold text-center">
            {{__('competition.info.competition') . ' : ' .$competition->title }}
        </h1>
    </div>

    <div class="mt-4 max-w-2xl mx-auto">
        @include('pages.user.users_order_list')
    </div>
    <div class="mt-2">
        {{$users->links()}}
    </div>

@endsection


