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
    <div class="space-y-8">
        @foreach ($responses as $response)
            @php
                $border_top_color = "border-t-danger border-t-2";
                $score_color_style = "text-danger border-danger";
                if ($response->final_score >= $response->question->max_score / 2) {
                    $border_top_color = "border-t-success border-t-2";
                    $score_color_style = "text-success border-success";
                }
            @endphp
            <div x-data="{ open: false }" 
                class="max-w-4xl mx-auto rounded-t-md 
                        shadow-xl border border-gray-300 {{ $border_top_color }}">
                <div @click="open = !open"
                    :class="open ? 'my-0' : 'my-2'"
                    class="cursor-pointer py-2 px-4 flex flex-row 
                            justify-between items-center gap-2">
                    <h3 class="hidden sm:block">
                        {{ $response->question->question_text }}
                    </h3>
                    <h3 class="text-primary text-lg sm:hidden">
                        {{ __('competition.question.the_question').' '.$loop->index+1 }}
                    </h3>

                    <div class="flex gap-2">
                        <span dir="ltr" 
                                class="px-2 py-1 whitespace-nowrap border-2 rounded-full {{ $score_color_style }}">
                            {{ $response->final_score }} / 
                            <span class="text-sm">{{ $response->question->max_score }}</span>
                        </span>
                        <span>
                            <i class="fa-solid fa-chevron-up text-primary text-end"  x-show="open"></i>
                            <i class="fa-solid fa-chevron-down text-primary text-end" x-show="!open"></i>
                        </span>
                    </div>
                </div>
                <div x-show="open"
                    class=" py-2 px-4 border border-t-0 rounded-b-md">
                    <hr class="border-t border-gray-300 mt-1 mb-4">

                    <div class="my-4 sm:my-6 sm:hidden">
                        <h4 class="capitalize text-primary font-semibold text-lg mb-2">
                            {{__('competition.question.question_text')}}
                        </h4>
                        <p> {{$response->question->question_text}}</p>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <h4 class=" capitalize text-primary font-semibold text-lg">
                                {{__('competition.response.response_text')}}
                            </h4>
                            <span class="text-sm sm:text-base text-primary">
                                {{$response->response_duration . ' ' .  __('messages.global.second')}}
                            <span>
                        </div>
                        <p> {{$response->response_text}}</p>
                    </div>

                    @php
                        $flags = json_decode($response->flags ?? '[]');
                    @endphp
                    @if ($flags)    
                        <div class="my-4 sm:my-6">
                            <h4 class="mb-2 capitalize text-danger font-semibold text-lg">
                                {{__('competition.response.penalty').' : '}}
                                <span dir="ltr">(-{{$response->penalty * 100}}%)</span>
                            </h4>
                            <div class="flex flex-wrap gap-2 mt-2 md:mt-4">
                                @foreach ($flags as $flag)
                                    <span class="px-2 py-1 text-sm text-danger whitespace-nowrap border-2 border-danger rounded-full">
                                        {{ __('competition.response.flag.'.$flag) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <h4 class="my-4 sm:my-6 capitalize text-primary font-semibold text-lg">
                        {{__('competition.response.score').' : '}}
                        <span dir="ltr" class="text-lg">
                            {{ $response->score }} / 
                            <span class="text-sm">{{ $response->question->max_score }}</span>
                        </span>
                    </h4> 
                    
                    <h4 class="my-4 sm:my-6 capitalize text-primary font-semibold text-lg">
                        {{__('competition.response.final_score').' : '}}
                        <span dir="ltr" class="text-lg">
                            {{ $response->final_score }} / 
                            <span class="text-sm">{{ $response->question->max_score }}</span>
                        </span>
                    </h4> 
                    
                </div>
            </div>
        @endforeach
    </div>
    <div class="block w-full overflow-x-auto hidden">
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

