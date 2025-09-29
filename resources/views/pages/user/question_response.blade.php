@extends('layouts.user.master')
@section('css')
    @section('title')
        {{ $competition_title . ' | ' . __('competition.response.response')}}
    @stop
@endsection


@section('content')
    <div class="p-4 rounded-sm-md shadow-2xl max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg md:text-xl text-primary font-bold capitalize ">{{ __('competition.question.the_question') }}</h2>
            <span class="inline-block px-4 text-primary border-2 border-primary rounded-xl ">
                {{ $question_count['current'] . '/' . $question_count['all'] }}
            </span>
        </div>
        <hr class="border-t border-gray-400" />
        <div class="my-8">
            <div class="flex justify-between items-center">
                <h3 class="capitalize md:text-lg font-semibold mb-2">{{__('competition.question.question_text')}}</h3>
                <p class="px-4 py-1 capitalize bg-primary-elegant text-white text-center rounded-xl ">
                    {{__('competition.question.max_score') . ': '. $question->max_score}}
                </p>
            </div>
            <p class="mt-4">
                {{ $question->question_text }}
            </p>
        </div>

        <x-alert
            type="danger"
            outline="true"
            size="sm"
            :closable="true"
            :title="__('messages.alert.type.danger')">
            {{__('messages.alert.content.leave_without_response')}}
        </x-alert>
        
        <div>
            <h3 class="capitalize md:text-lg font-semibold mb-2">{{__('competition.response.response')}}</h3>
            <form id="response_form" method="post" action="{{ route('user.competitions.level.response.store',['question' => $question]) }}" class="space-y-2">
                @csrf
                @method('post')
                <input type="hidden" name="keystrokes" id="keystrokes">
                <div>
                    <x-input-label for="response_text" :value=" ucwords(__('competition.response.response_text'))" />
                    <x-text-area id="response_text" name="response_text"  class="mt-1 block w-full">
                        {{old('response_text')}}
                    </x-text-area>
                    <x-input-error :messages="$errors->storeResponse->get('response_text')" class="mt-2" />
                </div>
                <div class="flex justify-end">
                    <x-button  color_type="success" >{{ __('form.actions.save') }}</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>

        // disable copy past
        //document.addEventListener('copy', e => e.preventDefault());
        //document.addEventListener('paste', e => e.preventDefault());
        //document.addEventListener('cut', e => e.preventDefault());
       // document.addEventListener('contextmenu', e => e.preventDefault());


        let isSubmitted = false;

        // Warn the user when they attempt to close the tab or browser
        window.addEventListener('beforeunload', function (e) {
            if (!isSubmitted) {
                e.preventDefault(); // Prevent the default action
                e.returnValue = "";
            }
        });

        // lisners
        let keystrokes = 0;

        document.getElementById('response_text').addEventListener('keydown', () => keystrokes++);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                sessionStorage.setItem('tab_switched', '1');
            }
        });

        document.getElementById('response_form').addEventListener('submit', () => {
            isSubmitted = true;
            document.getElementById('keystrokes').value = keystrokes;

            if (sessionStorage.getItem('tab_switched') === '1') {
                fetch("/record-tab-switch", { method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} });
            }
            sessionStorage.removeItem('tab_switched');
        });
    </script>
@endsection
