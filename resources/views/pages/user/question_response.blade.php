@extends('layouts.user.master')
@section('css')

    @section('title')
        {{\Illuminate\Support\Facades\Auth::user()->name . ' | ' . __('competition.response.response')}}
    @stop
@endsection


@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-lg" >
        <h1 class="text-xl font-bold capitalize">
            {{__('competition.response.response')}}
            {{' : ' . $level->name}}
        </h1>
    </div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:space-x-4">
        <!-- First Column (45% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[45%] mb-4 sm:mb-0">
            <x-collapsible-card
                :title="__('competition.question.info') . ' ' . $question_count['current'] . '/' . $question_count['all'] "
                type="info">
                <div class="space-y-4">
                    <div>
                        <h4 class="text-lg text-primary mb-2">{{__('competition.question.question_text')}}</h4>
                        <p>{{$question->question_text}}</p>
                    </div>
                    <div class="flex gap-2">
                        <h4 class="text-lg text-primary mb-2">{{__('competition.question.max_score')}}</h4>
                        <p>{{$question->max_score}}</p>
                    </div>
                    <div class="flex gap-2">
                        <h4 class=" text-lg text-primary mb-2">{{__('competition.question.duration')}}</h4>
                        <p>{{$question->duration .' '. __('messages.global.second')}}</p>
                    </div>

                    <x-alert
                        type="danger"
                        outline="true"
                        size="sm"
                        :title="__('messages.alert.type.danger')"
                    >
                        {{__('messages.alert.content.leave_without_response')}}
                    </x-alert>

                </div>
            </x-collapsible-card>

        </div>

        <!-- Second Column (50% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[50%]">
            <!-- Your content for the second column -->
            <x-collapsible-card :title="__('competition.response.response')" type="info">

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
            </x-collapsible-card>

        </div>
    </div>
@endsection

@section('custom_js')
    <script>

        // disable copy past
        document.addEventListener('copy', e => e.preventDefault());
        document.addEventListener('paste', e => e.preventDefault());
        document.addEventListener('cut', e => e.preventDefault());
        document.addEventListener('contextmenu', e => e.preventDefault());


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
