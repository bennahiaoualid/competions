@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('messages.global.global_order') }}
    @stop
@endsection


@section('content')
    <div class=" my-2 p-4 shadow-lg" >
        <h1 class="capitalize text-2xl font-bold text-center">
            {{__('messages.global.global_order') }}
        </h1>
        @if(isset($user_rank))
            @php
                $color = match ($user_rank) {
                    1 => 'bg-yellow-500',
                    2 => 'bg-gray-400',
                    3 => 'bg-amber-800',
                    default => 'bg-primary',
                };
            @endphp

            <a class="{{"block w-fit mx-auto mt-2 px-4  rounded-full capitalize text-white py-0.5 ". $color}}"
                href="{{ $users->url($user_page) }}">
                {{__('messages.global.your_order') . ' : '. $user_rank . ' ' . __('messages.global.check') }}
            </a>
        @endif
    </div>

    <div class="mt-4 mx-auto max-w-2xl">
        @if($users->count() > 0)
            <div class="block w-full overflow-x-auto">
                <table class="items-center bg-transparent w-full border-collapse ">
                    <thead>
                    <tr>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                            {{__('messages.global.order')}}
                        </th>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                            {{__('competition.info.competitor')}}
                        </th>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                            {{__('competition.response.score')}}
                        </th>
                        <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                            {{__('competition.response.response_duration').' ('.__('messages.global.second').')'}}
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($users as $user)
                        @php
                            // Calculate the global index
                            $globalIndex = 0;
                            if($users instanceof \Illuminate\Pagination\Paginator || $users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                $globalIndex = ($users->currentPage() - 1) * $users->perPage();
                        @endphp
                        <tr class="{{$user_rank == $loop->index + 1 + $globalIndex?'bg-primary text-white':''}}">
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                                @switch($loop->index + 1 + $globalIndex)
                                    @case(1)
                                        <img class="w-8 mx-auto" src="{{asset('assets/images/gold_medal.png')}}">
                                        @break
                                    @case(2)
                                        <img class="w-8 mx-auto" src="{{asset('assets/images/silver_medal.png')}}">
                                        @break
                                    @case(3)
                                        <img class="w-8 mx-auto" src="{{asset('assets/images/bronze_medal.png')}}">
                                        @break
                                    @default
                                        {{$loop->index + 1 + $globalIndex}}
                                @endswitch

                            </td>
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                                {{$user->name}}
                            </td>
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                                {{number_format($user->total_score,2)}}
                            </td>
                            <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs whitespace-nowrap p-4 text-center">
                                {{$user->total_duration}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
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

    </div>
    <div class="mt-2">
        <x-pagination :paginator="$users" />
    </div>

@endsection


