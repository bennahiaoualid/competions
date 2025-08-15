<?php

namespace App\Http\Controllers\GuestUsers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\Validator;
use App\Services\GuestUsers\UserGuestService;
use App\Http\Requests\GuestUsers\StoreResponseRequest;
use App\Services\Competition\GlobalQuestionGenerationService;

// The controller delegates all business logic to the service layer.
class UserGuestController extends Controller
{
    public function __construct(
        protected UserGuestService $userGuestService,
        protected GlobalQuestionGenerationService $aiQuestionService,
        protected SystemSettingService $systemSettingService

    ) {
    }

    public function index(): View
    {
        return view('pages.user.guest_users.prepare');
    }

    public function getRandomQuestion():  View|RedirectResponse
    {
        $result =  $this->userGuestService->getRandomQuestion();

        if($result['status'] === 'empty_question'){
            return view('pages.user.guest_users.no_question');
        }elseif($result['status'] === 'error'){
            return redirect()->back();
        }else{
            $question = $result['question'];
            return view('pages.user.guest_users.question_response', compact('question'));
        }
    }

    public function getRandomAIQuestion():  View|RedirectResponse
    {
        $result =  $this->userGuestService->getRandomQuestion('ai');

        if($result['status'] === 'empty_question'){
            return view('pages.user.guest_users.no_question');
        }elseif($result['status'] === 'error'){
            return redirect()->back();
        }else{
            $question = $result['question'];
            return view('pages.user.guest_users.question_response', compact('question'));
        }
    }

    public function getRandomPremiumQuestion():  View|RedirectResponse
    {
        $result =  $this->userGuestService->getRandomQuestion('premium');

        if($result['status'] === 'empty_question'){
            return view('pages.user.guest_users.no_question');
        }elseif($result['status'] === 'error'){
            return redirect()->back();
        }else{
            $question = $result['question'];
            return view('pages.user.guest_users.question_response', compact('question'));
        }
    }

    function storeResponse(StoreResponseRequest $request): View|RedirectResponse
    {

        $result =  $this->userGuestService->storeResponse($request->validated());
        if($result['status'] === 'success'){
            $data_result = $result['data_result'];
            return view('pages.user.guest_users.response_score', compact('data_result'));
        }else{
            return redirect()->route('global_questions.index');
        }
    }

    function globalUsersOrder() : View
    {
        $users_data = $this->userGuestService->globalUsersOrder();
        return view('pages.user.guest_users.global_order', $users_data);
    }

    function getGlobalUserResponse() : View|RedirectResponse
    {
        $result = $this->userGuestService->getGlobalUserResponse();
        if($result['status'] === 'success'){
            $questions = $result['questions'];
            return view('pages.user.guest_users.user_global_responses', compact('questions'));
        }else{
            return redirect()->back();
        }
    }

    public function aiQuestionGeneration() : View
    {
        $base_cost = $this->systemSettingService->getValueAsFloat('global_question_generating_cost');
        $difficulty_cost = $this->systemSettingService->getValueAsFloat('global_question_custom_difficulty_cost');
        $subject_cost = $this->systemSettingService->getValueAsFloat('global_question_custom_subject_cost');
        return view('pages.user.guest_users.ai_question_generation', compact('base_cost', 'difficulty_cost', 'subject_cost'));
    }

        /**
     * Generate AI question
     */
    public function generateAIQuestion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->aiQuestionService->getValidationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'exception_type' => 'validation_error',
                'error_type' => 'field_validation_failed',
                'message' => 'Please check your input fields',
                'user_message' => __('competition.ai.please_check_input_fields'),
                'context' => [
                    'validation_errors' => $validator->errors()->toArray(),
                    'fields' => array_keys($validator->errors()->toArray())
                ]
            ], 422);
        }
        
        $result = $this->aiQuestionService->generateAIQuestion($validator->validated());
        
        return response()->json($result);
    }
}
