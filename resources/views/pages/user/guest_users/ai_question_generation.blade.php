@extends('layouts.user.master')

@section('css')
    {{-- Any additional CSS here --}}
@endsection

@section('title')
    {{__('competition.ai.question_generation')}}
@stop

@section('content')
    {{-- Configuration data for JavaScript --}}
    @php
        $config = [
            'costs' => [
                'base' => $base_cost,
                'subject' => $subject_cost,
                'difficulty' => $difficulty_cost,
            ],
            'user' => [
                'id' => Auth::id(),
                'balance' => Auth::user()->coinBalance->balance ?? 0
            ],
            'routes' => [
                'store' => route('user.global_questions.ai_question_generation.store')
            ],
            'i18n' => [
                'generating_question' => __('competition.ai.generating_question'),
                'question_generated' => __('competition.ai.question_generated'),
                'generation_failed' => __('competition.ai.generation_failed'),
                'network_error' => __('competition.ai.network_error'),
                'unknown_error' => __('competition.ai.unknown_error'),
                'please_select_all_fields' => __('competition.ai.please_select_all_fields'),
                'please_wait' => __('competition.ai.please_wait'),
                'estimated_time' => __('competition.ai.estimated_time'),
                'dont_close_page' => __('competition.ai.dont_close_page'),
                'question_generation_success_message' => __('competition.ai.question_generation_success_message'),
                'view_question' => __('competition.ai.view_question'),
                'close' => __('competition.ai.close'),
                'try_again' => __('competition.ai.try_again'),
                'how_it_works' => __('competition.ai.how_it_works'),
                'feature' => [
                    'instant_generation' => __('competition.ai.feature.instant_generation'),
                    'multiple_subjects' => __('competition.ai.feature.multiple_subjects'),
                    'difficulty_levels' => __('competition.ai.feature.difficulty_levels'),
                    'auto_choices' => __('competition.ai.feature.auto_choices'),
                ],
                'generate_question' => __('competition.ai.generate_question'),
                'subject' => __('competition.ai.subject'),
                'select_subject' => __('competition.ai.select_subject'),
                'difficulty' => __('competition.ai.difficulty'),
                'select_difficulty' => __('competition.ai.select_difficulty'),
                'cost_breakdown' => __('competition.ai.cost_breakdown'),
                'base_cost' => __('competition.ai.base_cost'),
                'difficulty_cost' => __('competition.ai.difficulty_cost'),
                'subject_cost' => __('competition.ai.subject_cost'),
                'total_cost' => __('competition.ai.total_cost'),
                'your_balance' => __('competition.ai.your_balance'),
                
                'generation_started' => __('competition.ai.generation_started'),
                'generation_started_message' => __('competition.ai.generation_started'),
                'insufficient_balance_title' => __('competition.ai.insufficient_balance_title'),
                'service_unavailable_title' => __('competition.ai.service_unavailable_title'),
                'llm_error' => __('competition.ai.llm_error'),
                'process_error' => __('competition.ai.process_error'),
                'add_coins' => __('competition.ai.add_coins'),
                'service_unavailable' => __('competition.ai.service_unavailable'),
                'invalid_parameters' => __('competition.ai.invalid_parameters'),
                'please_check_selections' => __('competition.ai.please_check_selections'),
                'add_coins_coming_soon' => __('competition.ai.add_coins_coming_soon'),
                'generation_failed_generic' => __('competition.ai.generation_failed_generic'),
                'generation_question_success' => __('competition.ai.generation_question_success'),
                
                'validation_error_title' => __('competition.ai.validation_error_title'),
                'please_check_input_fields' => __('competition.ai.please_check_input_fields'),
                'please_fix_errors_below' => __('competition.ai.please_fix_errors_below'),
            ]
        ];
    @endphp

    <script id="question-generator-config" type="application/json">
        @json($config)
    </script>

    <div class="max-w-4xl mx-auto mt-4">
        <h1 class="capitalize text-xl font-bold mb-6 text-center" id="page-title">
            {{__('competition.ai.question_generation')}}
        </h1>
        
        <!-- Information Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-blue-800 mb-4" id="how-it-works-title">
                {{__('competition.ai.how_it_works')}}
            </h2>
            <ul class="space-y-2 text-blue-700" id="feature-list">
                <li class="flex items-center gap-3">
                    <i class="fa-regular fa-circle-check text-blue-600"></i>
                    <span id="feature-instant">{{__('competition.ai.feature.instant_generation')}}</span>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fa-regular fa-circle-check text-blue-600"></i>
                    <span id="feature-subjects">{{__('competition.ai.feature.multiple_subjects')}}</span>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fa-regular fa-circle-check text-blue-600"></i>
                    <span id="feature-difficulty">{{__('competition.ai.feature.difficulty_levels')}}</span>
                </li>
                <li class="flex items-center gap-3">
                    <i class="fa-regular fa-circle-check text-blue-600"></i>
                    <span id="feature-auto">{{__('competition.ai.feature.auto_choices')}}</span>
                </li>
            </ul>
        </div>

        <!-- AI Question Generation Form -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4" id="form-title">
                {{__('competition.ai.generate_question')}}
            </h2>
            
            <form id="aiQuestionForm" method="post" class="space-y-4">
                @csrf
                
                <!-- Subject Selection -->
                <div>
                    <x-input-label for="subject" :value="__('competition.ai.subject')" />
                    <x-form.select-box 
                        id="subject" 
                        name="subject" 
                        :placeholder="__('competition.ai.select_subject')"
                        :options="app(\App\Services\Competition\GlobalQuestionGenerationService::class)->getAvailableSubjects()"
                        required
                    />
                </div>

                <!-- Difficulty Selection -->
                <div>
                    <x-input-label for="difficulty" :value="__('competition.ai.difficulty')" />
                    <x-form.select-box 
                        id="difficulty" 
                        name="difficulty" 
                        :placeholder="__('competition.ai.select_difficulty')"
                        :options="app(\App\Services\Competition\GlobalQuestionGenerationService::class)->getAvailableDifficulties()"
                        required
                    />
                </div>

                <!-- Cost Calculation Display -->
                <div id="costCalculation" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-2" id="cost-breakdown-title">
                        {{__('competition.ai.cost_breakdown')}}
                    </h3>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600" id="base-cost-label">{{__('competition.ai.base_cost')}}</span>
                        <span id="baseCost" class="font-semibold text-gray-800">0 coins</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600" id="difficulty-cost-label">{{__('competition.ai.difficulty_cost')}}</span>
                        <span id="difficultyMultiplier" class="font-semibold text-gray-800">0 coins</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600" id="subject-cost-label">{{__('competition.ai.subject_cost')}}</span>
                        <span id="subjectCost" class="font-semibold text-gray-800">0 coins</span>
                    </div>
                    <hr class="my-2">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-800" id="total-cost-label">{{__('competition.ai.total_cost')}}</span>
                        <span id="totalCost" class="text-lg font-bold text-primary">0 coins</span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <span id="balance-label">{{__('competition.ai.your_balance')}}</span>: 
                        <span id="userBalance" class="font-semibold">{{ Auth::user()->coinBalance->balance ?? 0 }} coins</span>
                    </div>
                </div>

                <!-- Generate Button -->
                <div class="flex justify-center">
                    <x-button 
                        id="generateBtn" 
                        color_type="primary" 
                        size="md"
                        type="submit"
                    >
                        <i class="fas fa-magic me-2"></i>
                        <span id="generate-btn-text">{{__('competition.ai.generate_question')}}</span>
                    </x-button>
                </div>
            </form>
        </div>
    </div>

    <!-- AI Question Generation Modal -->
    <x-modal name="ai-question-generation" :show="false" maxWidth="2xl">
        <x-slot name="modalhead">
            <span id="modalTitle">{{__('competition.ai.generating_question')}}</span>
        </x-slot>

        <!-- Processing Indicator -->
        <div id="processingIndicator" class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
            <h3 class="text-lg font-semibold text-blue-800 mb-2" id="processing-title">
                {{__('competition.ai.generating_question')}}
            </h3>
            <p class="text-blue-600" id="processing-message">{{__('competition.ai.please_wait')}}</p>
            <div class="mt-4 text-sm text-blue-500">
                <p id="estimated-time-text">{{__('competition.ai.estimated_time')}}: 10-15 seconds</p>
                <p id="dont-close-text">{{__('competition.ai.dont_close_page')}}</p>
            </div>
        </div>

        <!-- Success Result -->
        <div id="successResult" class="hidden text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="fas fa-check text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-green-800 mb-4" id="success-title">
                {{__('competition.ai.question_generated')}}
            </h3>
            <p class="text-green-600 mb-6" id="success-message">
                {{__('competition.ai.question_generation_success_message')}}
            </p>
            
            <div class="flex justify-center gap-4">
                <x-button 
                    id="viewQuestionBtn"
                    islink="true"
                    href=""
                    color_type="success" 
                    size="md"
                >
                    <i class="fas fa-eye me-2"></i>
                    <span id="view-question-text">{{__('competition.ai.view_question')}}</span>
                </x-button>
                
                <x-button 
                    type="button"
                    color_type="primary" 
                    size="md"
                    x-on:click="$dispatch('close-modal', { detail: 'ai-question-generation' })"
                >
                    <span id="close-btn-text">{{__('competition.ai.close')}}</span>
                </x-button>
            </div>
        </div>

        <!-- Error Result with Enhanced Structure -->
        <div id="errorResult" class="hidden text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4">
                <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-red-800 mb-4" id="error-title">
                {{__('competition.ai.generation_failed')}}
            </h3>
            
            <!-- Error Type Indicator -->
            <div id="errorTypeIndicator" class="mb-4">
                <span id="errorTypeBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"></span>
            </div>
            
            <!-- Error reasons -->
            <div id="errorReasons" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-start">
                <!-- Error reasons will be inserted here -->
            </div>
            
            <!-- Dynamic Action Buttons -->
            <div id="errorActions" class="flex justify-center gap-4">
                <!-- Default retry button -->
                <x-button 
                    type="button"
                    id="defaultRetryBtn"
                    color_type="primary" 
                    size="md"
                    x-on:click="$dispatch('close-modal', { detail: 'ai-question-generation' })"
                >
                    <span id="try-again-text">{{__('competition.ai.try_again')}}</span>
                </x-button>
                
                <!-- Additional action buttons will be added dynamically -->
            </div>
        </div>

        <!-- Balance Error Modal -->
        <div id="balanceErrorModal" class="hidden text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-orange-100 rounded-full mb-4">
                <i class="fas fa-coins text-2xl text-orange-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-orange-800 mb-4">
                {{__('competition.ai.insufficient_balance_title')}}
            </h3>
            
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-orange-700">{{__('competition.ai.required_coins')}}:</span>
                    <span id="requiredCoins" class="font-semibold text-orange-800">0</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-orange-700">{{__('competition.ai.available_coins')}}:</span>
                    <span id="availableCoins" class="font-semibold text-orange-800">0</span>
                </div>
            </div>
            
            <div class="flex justify-center gap-4">
                <x-button 
                    type="button"
                    color_type="warning" 
                    size="md"
                    x-on:click="$dispatch('add-coins')"
                >
                    <i class="fas fa-plus me-2"></i>
                    <span>{{__('competition.ai.add_coins')}}</span>
                </x-button>
                
                <x-button 
                    type="button"
                    color_type="secondary" 
                    size="md"
                    x-on:click="$dispatch('close-modal', { detail: 'ai-question-generation' })"
                >
                    <span>{{__('competition.ai.close')}}</span>
                </x-button>
            </div>
        </div>
    </x-modal>
@endsection
@vite('resources/js/ai-question-generator.js')
