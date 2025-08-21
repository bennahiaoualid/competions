@extends('layouts.user.master')
@section('css')

    @section('title')
        {{__('links.global_user.global_responses') }}
    @stop
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-end mx-auto my-4 py-4 px-2 md:p-6 shadow-sm bg-white rounded-lg">
        <h1 class="capitalize text-2xl font-bold text-gray-800">{{__('links.global_user.global_responses')}}</h1>
        <div class="text-sm text-gray-600">
            {{__('competition.response.completed_questions')}}: {{ $questions->total() }}
        </div>
    </div>

    <div class="space-y-6">
        @foreach($questions as $question)
            <x-collapsible-card :title="__('competition.question.the_question').' '.$loop->index+1" type="primary">

                <!-- Question Header with Metadata -->
                <div class="md:mb-6 px-2 py-4 md:p-4 bg-gray-50 rounded-lg">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <!-- Question Type Badge -->
                        <div class="flex items-center flex-wrap gap-2">
                            @if($question->isAiGeneratedByUser(\Illuminate\Support\Facades\Auth::id()))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-robot me-1"></i>
                                    {{__('competition.question.type.ai')}}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-question-circle me-1"></i>
                                    {{__('competition.question.type.free')}}
                                </span>
                            @endif

                            <!-- Premium Badge if applicable -->
                            @if($question->isPremiumForUser(\Illuminate\Support\Facades\Auth::id()))
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-crown me-1"></i>
                                    {{__('competition.question.type.premium')}}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Completion Time -->
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-clock me-1"></i>
                            {{__('competition.response.completed_at')}}: {{ $question->responses->first()->created_at->diffForHumans() }}
                        </div>
                    </div>
                    
                    <!-- Question Text with Dynamic Direction -->
                    <div class="my-6 md:my-8">
                        <h3 class="text-xl text-center md:text-start capitalize font-semibold text-gray-800 mb-3">
                            {{__('competition.question.question_text')}}
                        </h3>
                        
                        <!-- Question Content Container with Direction Clarity -->
                        <div class="border-2 border-gray-200 rounded-lg overflow-hidden">
                            <!-- Direction Indicator -->
                            <div class="bg-blue-50 px-3 py-2 border-b border-gray-200">
                                <span class="text-sm text-blue-600 font-medium">
                                    <i class="fas {{ $question->text_direction === 'rtl' ? 'fa-arrow-right' : 'fa-arrow-left' }} me-1"></i>
                                    {{ $question->text_direction === 'rtl' ? __('competition.question.read_right_to_left') : __('competition.question.read_left_to_right') }}
                                </span>
                            </div>
                            
                            <!-- Question Text with Dynamic Direction -->
                            <div class="p-4" dir="{{ $question->text_direction }}">
                                <p class="text-gray-700 text-base leading-relaxed">
                                    {{$question->question_text}}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Question Score -->
                        <div class="mt-3">
                            <span class="inline-flex items-center px-3 py-2 rounded-lg bg-primary/10 border border-primary/20">
                                <span class="text-sm font-medium text-primary me-2">{{__('competition.question.max_score')}}:</span>
                                <span class="text-lg font-bold text-primary">{{$question->score}}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- User Responses with Enhanced Display -->
                <div class="space-y-4">
                    <h4 class="text-md font-semibold text-gray-700 mb-3">
                        {{__('competition.response.your_answers')}}
                    </h4>
                    
                    @foreach($question->responses as $response)
                        @if($response->choice_id)
                            @php
                                $isCorrect = $response->score > 0;
                                $borderColor = $isCorrect ? 'border-green-200' : 'border-red-200';
                                $bgColor = $isCorrect ? 'bg-green-50' : 'bg-red-50';
                                $textColor = $isCorrect ? 'text-green-800' : 'text-red-800';
                                $scoreColor = $isCorrect ? 'bg-green-500' : 'bg-red-500';
                            @endphp
                            
                            <div class="p-4 rounded-lg border-2 {{ $borderColor }} {{ $bgColor }} transition-colors">
                                <!-- Choice Text with Dynamic Direction -->
                                <div class="flex items-center justify-between ">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full {{ $scoreColor }} flex items-center justify-center">
                                            <i class="fas {{ $isCorrect ? 'fa-check' : 'fa-times' }} text-white text-sm"></i>
                                        </div>
                                        <span class="hidden md:block text-gray-800 font-medium" style="text-align: {{ $question->text_direction === 'rtl' ? 'right' : 'left' }}">
                                            {{$response->choice?->choice_text ?? __('competition.response.void_choice')}}
                                        </span>
                                    </div>
                                    
                                    <!-- Score and Time (consistent positioning) -->
                                    <div class="flex items-center gap-3">
                                        <div class="text-sm text-gray-500">
                                            <i class="fas fa-stopwatch me-1"></i>
                                            {{ $response->response_duration }}s
                                        </div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold text-white {{ $scoreColor }}">
                                            {{ number_format($response->score, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-gray-800 font-medium mt-4 text-center md:hidden">
                                    {{$response->choice?->choice_text ?? __('competition.response.void_choice')}}
                                </p>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Performance Summary -->
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-600">{{__('competition.response.total_time')}}:</span>
                            <span class="text-sm font-semibold text-gray-800">
                                {{ $question->responses->sum('response_duration') }} {{__('messages.global.seconds')}}
                            </span>
                        </div>
                        
                        @if($question->explanation)
                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <button type="button" 
                                        onclick="toggleExplanation({{ $loop->index }})"
                                        class="text-sm text-primary hover:text-primary-dark font-medium">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    {{__('competition.question.show_explanation')}}
                                </button>
                                <div id="explanation-{{ $loop->index }}" class="hidden mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p class="text-sm text-blue-800 leading-6" dir="{{ $question->text_direction }}" style="text-align: {{ $question->text_direction === 'rtl' ? 'right' : 'left' }}">
                                        {{ $question->explanation }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </x-collapsible-card>
        @endforeach
        
        <!-- Pagination with Better Styling -->
        <div class="mt-8">
            {{ $questions->links() }}
        </div>
    </div>
</div>
@endsection

@section('custom_js')
<script>
function toggleExplanation(index) {
    const explanation = document.getElementById(`explanation-${index}`);
    if (explanation) {
        explanation.classList.toggle('hidden');
    }
}
</script>
@endsection

