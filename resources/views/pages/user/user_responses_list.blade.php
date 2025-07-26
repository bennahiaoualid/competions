@extends('layouts.user.master')
@section('css')

    @section('title')
        {{\Illuminate\Support\Facades\Auth::user()->name . ' | ' . __('competition.response.list')}}
    @stop
@endsection


@section('content')
    <div class="flex justify-between items-center my-4 p-4 shadow-lg" >
        <h1 class="text-xl font-bold capitalize">
            {{__('competition.response.list')}}
            {{' : ' . $level->name}}
        </h1>
    </div>
    <div class="block w-full overflow-x-auto">
        <table class="items-center bg-transparent w-full border-collapse ">
            <thead>
            <tr>
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs md:text-base uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                    {{__('competition.response.response_text')}}
                </th>
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs md:text-base uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                    {{__('competition.response.response_duration') . ' (' . __('messages.global.second') . ')'}}
                </th>
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs md:text-base uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                    {{__('competition.response.score')}}
                </th>
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs md:text-base uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                    {{__('competition.response.final_score')}}
                </th>
                <th class="px-6 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs md:text-base uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center">
                    {{__('competition.question.question_text')}}
                </th>
            </tr>
            </thead>

            <tbody>
            @foreach($responses as $response)
                <tr class="hover:bg-gray-100 border-b border-gray-500">
                    <td class="text-wrap border-t-0 px-6 align-middle border-l-0 border-r-0 text-sm whitespace-nowrap p-4 text-start text-blueGray-700 ">
                        {{$response->response_text}}
                        <div class="flex flex-wrap gap-2 mt-2 md:mt-4">
                            @foreach (json_decode($response->flags ?? '[]') as $flag)
                                <span class="text-sm text-warning whitespace-nowrap border-2 border-warning rounded-full p-2">
                                    {{ __('competition.response.flag.'.$flag) }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="border-t-0 px-6 align-middle border-l-0 border-r-0 text-xs md:text-base whitespace-nowrap p-4 text-center">
                        {{$response->response_duration}}
                    </td>
                    <td class="border-t-0 px-6 align-center border-l-0 border-r-0 text-xs md:text-base whitespace-nowrap p-4 text-center">
                        {{$response->score . ' / ' . $response->question->max_score}}
                    </td>
                    <td class="border-t-0 px-6 align-center border-l-0 border-r-0 text-xs md:text-base whitespace-nowrap p-4 text-center">
                        {{$response->final_score . ' / ' . $response->question->max_score}}
                    </td>
                    <td x-data class=" align-center">
                        <x-button data-text="{{$response->question->question_text}}" class="show_question"
                                x-on:click="$dispatch('open-modal', { detail: 'show' , value:'{{$response->question->question_text}}' })">
                            <x-slot:icon>
                                <i class="fa-solid fa-eye me-2"></i>
                            </x-slot:icon>
                            {{__("form.actions.show")}}
                        </x-button>
                        
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <x-pagination :paginator="$responses" />
    </div>

    {{-- show question content in modal --}}
    <x-modal name="show" title="My Modal" :show="false">
        <x-slot:modalhead>
            {{__("competition.question.question_text")}}
        </x-slot>
        <div>
            <p x-text="inputValue"></p> <!-- Displays the passed question text -->
        </div>
    </x-modal>
@endsection
@section('custom_js')
    <script>
        const buttons = document.querySelectorAll('.show_question');

        console.log(buttons);
    </script>
@endsection

