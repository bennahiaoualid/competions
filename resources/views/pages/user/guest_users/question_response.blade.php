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
        </h1>
    </div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:space-x-4">
        <!-- First Column (45% on sm and above, 100% on small screens) -->
        <div class="w-full sm:w-[45%] mb-4 sm:mb-0">
            <x-collapsible-card
                :title="__('competition.question.info') "
                type="info">
                <div class="space-y-4">
                    <div>
                        <h4 class="text-lg text-primary mb-2">{{__('competition.question.question_text')}}</h4>
                        <p>{{$question->question_text}}</p>
                    </div>
                    <div class="flex gap-2">
                        <h4 class="text-lg text-primary mb-2">{{__('competition.question.max_score')}}</h4>
                        <p>{{$question->score}}</p>
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

                <form id="response_form" method="post" action="{{ route('user.global_questions.response.store') }}" class="space-y-2">
                    @csrf
                    @method('post')
                    <input type="hidden" name="question_id" value="{{$question->id}}">
                    <input type="hidden" id="choice_id" name="choice_id" value="">
                @foreach($question->choices as $choice)
                        <button class="choice-button block min-w-full px-4 py-1 text-primary font-bold border border-primary rounded-md shadow-md hover:bg-primary hover:text-white transition focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75"
                                data-choice-id="{{ $choice->id }}">
                            {{ $choice->choice_text }}
                        </button>
                    @endforeach
                </form>
            </x-collapsible-card>

        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        /* the process for submitting the response form */

        const choiceButtons = document.querySelectorAll('.choice-button');
        const choiceForm = document.getElementById('response_form');
        const choiceInput = document.getElementById('choice_id');

        choiceButtons.forEach(button => {
            button.addEventListener('click', function () {
                choiceInput.value = this.getAttribute('data-choice-id'); // Set the hidden input value to the selected choice ID
                choiceForm.submit(); // Submit the form
            });
        });




        /* prevent users from leaving the page without warning */
        let isSubmitted = false;

        // Warn the user when they attempt to close the tab or browser
        window.addEventListener('beforeunload', function (e) {
            if (!isSubmitted) {
                e.preventDefault(); // Prevent the default action
                e.returnValue = "";
            }
        });
        // Listen for form submission to prevent the warning on valid submission
        document.getElementById('response_form').addEventListener('submit', function() {
            isSubmitted = true; // Mark form as submitted
        });
    </script>
@endsection
