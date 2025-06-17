@extends('layouts.user.master')
@section('css')

    @section('title')
        hello
    @stop
@endsection


@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-info-card class="max-w-sm" type="success" size="md"
                    :content="__('competition.info.active')"
                    value="{{$data['active_comp']}}"
                    :link="route('competitions')">
            <x-slot:icon>
                <i class="fa-solid fa-ranking-star"></i>
            </x-slot:icon>
        </x-info-card>
        <x-info-card class="max-w-sm" type="warning" size="md"
                    :content="__('competition.info.coming')"
                    value="{{$data['coming_comp']}}"
                    :link="route('competitions')">
            <x-slot:icon>
                <i class="fa-solid fa-ranking-star"></i>
            </x-slot:icon>
        </x-info-card>
        <x-info-card class="max-w-sm" type="info" size="md"
                    :content="__('competition.info.finished')"
                    value="{{$data['finished_comp']}}"
                    :link="route('competitions')">
            <x-slot:icon>
                <i class="fa-solid fa-ranking-star"></i>
            </x-slot:icon>
        </x-info-card>
    </div>

    {{-- latest user compeitions --}}
    <div class="mt-4 md:max-w-4xl">
        <x-collapsible-card :title="__('links.competition.list')" type="info">
        @if(count($data['latestCompetitions']) > 0)
            <div class="block w-full overflow-x-auto mx-auto">
                <table class="items-center bg-transparent w-full border-collapse ">
                    <thead>
                    <tr>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-center">
                            {{__('messages.global.order')}}
                        </th>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-center">
                            {{__('competition.info.title')}}
                        </th>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 uppercase border-l-0 border-r-0 whitespace-nowrap font-bold text-center">
                            {{__('competition.info.competitors')}}
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($data['latestCompetitions'] as $competition)
                        <tr>
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-sm md:text-base whitespace-nowrap p-4 text-center">
                                {{$competition['user_rank']}}
                            </td>
                            <td class="text-wrap border-t-0 px-6 align-middle border-l-0 border-r-0 text-sm md:text-base whitespace-nowrap p-4">
                                {{$competition['competition']->title}}
                            </td>
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0  text-sm md:text-base whitespace-nowrap p-4 text-center">
                                {{$competition['total_competitors']}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
            <div class="mt-2 flex justify-center">
                <x-button :islink="true" href="{{route('competitions.detail',['competition'=>$competition['competition']])}}">
                    {{__('messages.global.see_all')}}
                </x-button>
            </div>
        @else
            <div class="text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-folder-open text-6xl"></i>
                </div>
                <div class="text-gray-700 text-lg font-semibold">
                    {{__('messages.global.no_records')}}
                </div>
            </div>
        @endif
        </x-collapsible-card>
    </div>
@endsection

