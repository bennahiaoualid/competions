@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.global_user.global_questions')}}
    @stop
@endsection

@section('page_title')
    {{ __('links.admin.dashboard') }}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm" >
        <h1 class="capitalize text-xl font-bold">{{__('links.global_user.global_questions')}}</h1>
        <div x-data>
            <x-button
                name="myModal"
                x-on:click="$dispatch('open-modal', { detail: 'myModal' })">
                <x-slot:icon>
                    <i class="fa-solid fa-plus me-2"></i>
                </x-slot:icon>
               {{__("form.actions.add")}}
            </x-button>
        </div>
    </div>

    <x-modal name="myModal" title="My Modal" :show="$errors->hasBag('createQuestion')">
        <x-slot:modalhead>
            {{__("form.global_question.add")}}
        </x-slot>
        <form id="add-form" method="post" action="{{ route('admin.global_questions.store') }}" class="space-y-2">
            @csrf
            @method('post')

            <div>
                <x-input-label for="question_text" :value=" ucwords(__('competition.question.question_text'))" />
                <x-text-area id="question_text" name="question_text"  class="mt-1 block w-full whitespace-pre-wrap" min="3"></x-text-area>
                <x-input-error :messages="$errors->createQuestion->get('question_text')" class="mt-2" />
            </div>

            <div class="flex gap-4">
                <div>
                    <x-input-label for="score" :value=" ucwords(__('competition.response.score'))" />
                    <x-text-input name="score" type="number" min="1"
                                  lang="en" value="1" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->createQuestion->get('score')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="duration" :value=" ucwords(__('competition.response.response_duration') .' ('. __('messages.global.seconds')).')'" />
                    <x-text-input name="duration" type="number" min="60"
                                  lang="en" value="60" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->createQuestion->get('duration')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="txt_direction" :value="ucwords(__('messages.global.text_dir'))" />
                <div class="flex gap-6 items-center">
                    <div>
                        <x-form.select-box id="txt_direction" name="txt_direction"  :options="[
                                ['value' => 'ltr', 'text' => __('messages.global.ltr'), 'selected' => true],
                                ['value' => 'rtl', 'text' => __('messages.global.rtl'), 'selected' => false],
                            ]">
                        </x-form.select-box>
                    </div>
                </div>
                <x-input-error :messages="$errors->createQuestion->get('txt_direction')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="choices_number" :value=" ucwords(__('competition.global.choices'))" />
                <div>
                    <x-alert
                        type="info"
                        outline="true"
                        size="sm"
                        :title="__('messages.alert.type.info')"
                    >
                        {{__('messages.alert.content.correct_choice')}}
                    </x-alert>
                </div>
                <div class="flex gap-6 items-center">
                    <div>
                        <x-form.select-box id="choices_number"   :options="[
                                ['value' => 2, 'text' => 2, 'selected' => true],
                                ['value' => 3, 'text' => 3, 'selected' => false],
                                ['value' => 4, 'text' => 4, 'selected' => false],
                                ['value' => 5, 'text' =>5, 'selected' => false],
                            ]">
                        </x-form.select-box>
                    </div>
                    <div>
                        <button type="button" id="generate_choices" class="block px-4 py-1 bg-primary text-white rounded-md" id="generate_chices">
                            {{__('form.actions.generate')}}
                        </button>
                    </div>
                </div>
                <div id="choices_container">

                </div>
            </div>

        </form>
        <x-slot:modalfooter>
            <div class="flex justify-end">
                <x-button form="add-form" color_type="success" >{{ __('form.actions.save') }}</x-button>
            </div>
        </x-slot>
    </x-modal>

    <x-modal name="delete" title="My Modal" :show="false">
            <x-slot:modalhead>
                {{__("form.global_question.delete")}}
            </x-slot>
            <form id="delete-form" method="post" action="{{route("admin.global_question.delete")}}" class="space-y-2">
                @csrf
                @method('post')

                <div>
                    <input type="hidden" name="id" x-model="inputValue"/>
                    <x-alert
                        type="warning"
                        outline="true"
                        size="sm"
                        :title="__('messages.alert.type.warning')"
                    >
                        {{__('form.actions.confirm_delete')}}
                    </x-alert>
                </div>

            </form>
            <x-slot:modalfooter>
                <div class="flex justify-end">
                    <x-button form="delete-form" color_type="danger" >{{ __('form.actions.delete') }}</x-button>
                </div>
            </x-slot>
        </x-modal>

    <div class="overflow-x-auto max-w-[90vw] pt-2">
        <livewire:global-questions-table/>
    </div>
@endsection
@section('custom_js')
    <script>
       const generateChoicesBtn = document.getElementById('generate_choices');
       const choicesNumber = document.getElementById('choices_number');
       const choicesContainer = document.getElementById('choices_container');
       generateChoicesBtn.addEventListener('click',()=>{
            let choices_int = parseInt(choicesNumber.value);
            if (1 < choices_int < 6 ){
                choicesContainer.innerHTML = '';
                choicesContainer.insertAdjacentHTML('afterbegin',`
                    <x-input-label :value=" ucwords(__('competition.global.choice'))" />
`               );
                let choicesView = document.createElement('div');
                let choice_ele = "";
                for (let i = 0; i <= choices_int-1; i++) {
                    let selected = i === 0;
                    choice_ele += `
                        <div class="">
                            <x-text-area name="choice[]"  class="mt-1 block w-full" min="3" rows="1"></x-text-area>
                            <x-input-error :messages="$errors->createQuestion->get('choice.${i}')" class="mt-2" />
                        </div>
                    `;
                }
                choicesView.innerHTML = choice_ele;
                choicesContainer.appendChild(choicesView);
            }
       })

       document.getElementById('txt_direction').addEventListener('change',function (event) {
           document.getElementById('question_text').dir = event.target.value;
       })
    </script>
@endsection
