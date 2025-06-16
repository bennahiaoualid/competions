@extends('layouts.admin.master')
@section('css')
    @section('title')
        {{__('competition.question.list')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.competition') . ' ' . $level->competition->title }}
@endsection

@section('content')
    <div class="fmy-2 p-4 shadow-card" >
        <div class="flex justify-between">
            <h1 class="text-xl font-bold mb-2">
                {{__('competition.question.list')}}
                <span> : {{$level->name}}</span>
            </h1>
            {{-- @if($questions->count() == 0)
                <x-button form="add" color_type="success" >{{ __('form.actions.save') }}</x-button>
            @endif --}}
            @can($level->canEditQuestion())
                <div x-data>
                    <x-button
                        name="add_questions"
                        x-on:click="$dispatch('open-modal', { detail: 'add_question' })">
                        <x-slot:icon>
                            <i class="fa-solid fa-plus me-2"></i>
                        </x-slot:icon>
                        {{__("form.actions.add")}}
                    </x-button>
                </div>
            @endcan
        </div>
        
        <div class="mt-4">
            @include("pages.admin.competitions.questions_list")
        </div>

        <div class="flex justify-end items-center gap-4 mt-4 flex-wrap">
            {{-- Laravel Pagination --}}
            {{ $questions->appends(['perPage' => request('perPage')])->links() }}
        
            {{-- Per Page Dropdown --}}
            <form method="GET">
                <x-form.select-box name="perPage" onchange="this.form.submit()"  :options="[
                    ['value' => 5, 'text' => __('Show') . ' 5', 'selected' => request('perPage', 5) == 5],
                    ['value' => 10, 'text' => __('Show') . ' 10', 'selected' => request('perPage', 5) == 10],
                    ['value' => 25, 'text' => __('Show') . ' 25', 'selected' => request('perPage', 5) == 25],
                    ['value' => 50, 'text' => __('Show') . ' 50', 'selected' => request('perPage', 5) == 50],
                    ['value' => 100, 'text' => __('Show') . ' 100', 'selected' => request('perPage', 5) == 100],
                ]">
                </x-form.select-box>
            </form>
        </div>
        
    </div>

    <!-- add new level form -->
<x-modal name="add_question" title="add_question" maxWidth="full" title_size="text-4xl" :show="$errors->hasBag('createQuestion')">
    <x-slot:modalhead>
        {{__("form.question.add")}}
    </x-slot>

    @if($errors->hasBag('createQuestion'))
        <x-alert type="danger" outline="true" size="sm" :closable="true" :title="__('messages.alert.type.danger')">
            {{ $errors->createQuestion->first() }}
        </x-alert>
    @endif
    
    <div class="w-full max-w-4xl text-center mx-auto">
        <h3 class="text-xl mb-2 capitalize">{{__("form.question.add")}}</h3>
        <div class="flex gap-6 items-center justify-center">
            <x-text-input id="questions_number" name="questions_number" type="number" min="1" max="10" value="1" class="mt-1 block w-full"  />
            <x-button type="button" id="generate_questions">
                <x-slot:icon>
                    <i class="fa-solid fa-plus me-2"></i>
                </x-slot:icon>
                {{__("form.actions.generate")}}
            </x-button>
        </div>
    </div>

    <form id="add_question_form" method="post" action="{{ route('admin.competitions.level.question.store', ['level' => $level]) }}" class="space-y-2">
        @csrf
        @method('post')
        <div id="questions_container">
        </div>
    </form>
    <x-slot:modalfooter>
        <div class="flex justify-end">
            <x-button form="add_question_form" color_type="success" >{{ __('form.actions.save') }}</x-button>
        </div>
    </x-slot>
</x-modal>
@endsection

@section('custom_js')
    <script>
        const generateQuestionsBtn = document.getElementById('generate_questions');
        const questionsNumber = document.getElementById('questions_number');
        const questionsContainer = document.getElementById('questions_container');
        generateQuestionsBtn.addEventListener('click',()=>{
            let questions_int = parseInt(questionsNumber.value);
            if (questions_int > 0 && questions_int < 11){
                questionsContainer.innerHTML = '';
                let tableHTML = `
                    <div class="block w-full overflow-x-auto mt-4">
                        <table class="items-center bg-transparent w-full border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 60px">
                                        {{__('messages.global.no')}}
                                    </th>
                                    <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: auto">
                                        {{__('competition.question.question_text')}}
                                    </th>
                                    <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 100px">
                                        {{__('competition.question.max_score')}}
                                    </th>
                                    <th class="px-2 bg-slate-300 text-blueGray-500 align-middle border border-solid border-blueGray-100 py-3 text-xs uppercase border-l-0 border-r-0 whitespace-nowrap font-semibold text-center" style="width: 120px">
                                        {{__('competition.question.duration') .' ( '. __('messages.global.second').' )'}}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>`;

                for (let i = 0; i < questions_int; i++) {
                    tableHTML += `
                        <tr>
                            <td class="px-2 align-middle border border-solid border-blueGray-100 py-3 text-xs border-l-0 border-r-0 whitespace-nowrap text-center">
                                ${String(i + 1).padStart(3, '0')}
                            </td>
                            <td class="px-2">
                                <x-text-area name="question_text[]" class="mt-1 block w-full"></x-text-area>
                                <x-input-error :messages="$errors->createQuestion->get('question_text.${i}')" class="mt-2" />
                            </td>
                            <td class="px-2">
                                <x-text-input name="max_score[]" type="number" min="1" max="999" lang="en" value="1" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->createQuestion->get('max_score.${i}')" class="mt-2" />
                            </td>
                            <td class="px-2">
                                <x-text-input name="duration[]" type="number" min="30" max="9999" lang="en" value="30" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->createQuestion->get('duration.${i}')" class="mt-2" />
                            </td>
                        </tr>`;
                }

                tableHTML += `
                            </tbody>
                        </table>
                    </div>`;

                questionsContainer.innerHTML = tableHTML;
            }
        });
    </script>
@endsection

