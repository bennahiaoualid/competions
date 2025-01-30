@extends('layouts.admin.master')
@section('css')

    @section('title')
        {{__('links.competition.auditing_responses')}}
    @stop
@endsection

@section('page_title')
    {{ __('competition.info.competition') . ' : '  .$level->competition->title}}
@endsection

@section('content')
    <div class="flex justify-between items-center my-2 p-4 shadow-sm">
        <h1 class="text-lg font-bold">
            {{ __('competition.level.level') . ' : ' . $level->name }}
        </h1>
    </div>

    <div class="my-2 max-w-2xl">
        <x-alert
            type="warning"
            outline="true"
            size="sm"
            :title="__('messages.alert.type.warning')"
        >
            {{__('messages.alert.content.you_cant_change_audited_responses')}}
        </x-alert>

        <x-alert
            type="info"
            outline="true"
            size="sm"
            :closable="true"
            :title="__('messages.alert.type.info')"
        >
            {{__('messages.alert.content.response_final_score_calc')}}
        </x-alert>

        <form action="{{ route('admin.auditor.users.responses.audit_score', [$level->id,$user->anonymized_identifier]) }}" method="POST" class="space-y-4" id="responses_score">
        @csrf
        @method('post')
        <input type="hidden" name="user_id" value="{{\Illuminate\Support\Facades\Crypt::encrypt($user->anonymized_identifier)}}">
        <input type="hidden" name="level_id" value="{{\Illuminate\Support\Facades\Crypt::encrypt($level->id)}}">

    @foreach($questions as $question)
            <div class="mb-6 border-2 border-primary rounded-md py-2 px-4 " id="response_{{  $question->response?->id }}">
                <h2 class="text-lg font-semibold">
                    {{ __('competition.question.the_question') .' '. $loop->index + 1  . ' : ' }}
                    <span class="ms-2 text-base text-gray-700"> {{ $question->question_text }}</span>
                </h2>

                @php
                    $response = $question->responses->first(); // There should be only one response for the given user.
                @endphp

                <p class="mt-2 md:mt-4">
                    <strong>{{__('competition.response.user_response')}} :</strong>
                    <span class="ms-2 text-base text-gray-700">
                        {{ $response ? $response->response_text : 'No response submitted' }}
                    </span>
                </p>

                @if($response)
                    <div class="flex gap-4 mt-2 md:mt-4">
                        <div>
                            <x-input-label for="max_score_{{ $response->id }}" :value=" ucwords(__('competition.question.max_score'))" />
                            <x-text-input id="max_score_{{ $response->id }}" type="number" lang="en" value="{{ $question->max_score }}" class="mt-1 block w-full" disabled />
                        </div>
                       <div>
                           @if($response->admin_id == null)
                               <x-input-label for="score_{{ $response->id }}" :value=" ucwords(__('competition.response.score'))" />

                               <x-text-input name="scores[{{ $response->id }}]" type="number"  lang="en" value="{{ $response->score }}" class="mt-1 block w-full scores"
                                             id="score_{{ $response->id }}" min="0" data-id="{{ $response->id }}" max="{{ $question->max_score }}" />
                               <x-input-error :messages="$errors->auditUserResponses->get('scores.'.  $response->id)" class="mt-2" />
                           @else
                               <x-input-label for="score_{{ $response->id }}" :value=" ucwords(__('competition.response.score'))" />
                               <x-text-input id="score_{{ $response->id }}" type="number" lang="en" value="{{ $response->score }}" class="mt-1 block w-full" disabled />
                           @endif

                       </div>
                    </div>

                    <div class="flex gap-4 mt-2 md:mt-4 items-center">
                        <div>
                            <x-input-label for="question_duration_{{ $response->id }}" :value=" ucwords(__('competition.question.duration'))" />
                            <x-text-input id="question_duration_{{ $response->id }}" type="number" lang="en" value="{{ $question->duration }}" class="mt-1 block w-full" disabled />
                        </div>

                        <div>
                            <x-input-label for="response_duration_{{ $response->id }}" :value=" ucwords(__('competition.response.response_duration'))" />
                            <x-text-input id="response_duration_{{ $response->id }}" type="number" lang="en" value="{{ $response->response_duration }}" class="mt-1 block w-full" disabled />
                        </div>
                        @if($response->admin_id == null)
                        <div>
                            <x-input-label for="final_score_{{ $response->id }}" :value=" ucwords(__('competition.response.final_score'))" />
                            <x-text-input id="final_score_{{ $response->id }}" type="number" lang="en" value="0" class="mt-1 block w-full" disabled />
                        </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
        <div class="flex justify-end">
            <x-button form="responses_score" color_type="success" class="my-1" >{{ __('form.actions.save') }}</x-button>
        </div>
    </form>
    </div>

@endsection
@section('custom_js')

    <script>
        const scores = document.querySelectorAll('.scores');
        if(scores.length > 0){
            scores.forEach((score) => {
                let id = score.getAttribute('data-id');
                score.addEventListener('change',(event)=>{
                    let final_score = document.getElementById('final_score_' + id);
                    let giving_score = parseFloat(event.target.value);
                    let duration =parseFloat( document.getElementById('question_duration_' + id).value);
                    let response_duration =parseFloat( document.getElementById('response_duration_' + id).value);
                    if (response_duration >= duration || giving_score == 0){
                        final_score.value = (giving_score / 2).toFixed(2);

                    } else{
                        final_score.value = (giving_score - response_duration / duration * giving_score / 2).toFixed(2);
                    }
                })
            })
        }


    </script>
@endsection
