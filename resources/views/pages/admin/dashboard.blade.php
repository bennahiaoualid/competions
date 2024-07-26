@extends('layouts.admin.master')
@section('css')

    @section('title')
        hello
    @stop
@endsection


@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-info-card class="max-w-sm" type="info" size="md"
                     :content="__('links.admin.admins')" value="{{$count['admin']}}">
            <x-slot:icon>
                <i class="fas fa-user-plus"></i>
            </x-slot:icon>
        </x-info-card>
        <x-info-card class="max-w-sm" type="success" size="md"
                     :content="__('links.user.list')" value="{{$count['user']}}">
            <x-slot:icon>
                <i class="fas fa-user-plus"></i>
            </x-slot:icon>
        </x-info-card>
    </div>
@endsection
