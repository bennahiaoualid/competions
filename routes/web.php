<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Payment\PaymentProofController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
Route::get('/test', function () {
    try {
        // Use Gemini service directly for testing
        $geminiService = app(\App\Services\LLM\GeminiService::class);
        
        // Simple prompt for testing
        $prompt = "
          {
  \"system_prompt\": \"ROLE: You are an expert competition scorer with advanced natural language understanding capabilities.\\n\\nTASK: Analyze and score user responses for a competition question. Compare each user's answer against the perfect response using semantic understanding, not just literal matching.\\n\\nSCORING CRITERIA:\\n- Perfect match (semantic or literal): Full points (max_score)\\n- Partially correct with minor errors: 70-90% of max_score\\n- Contains correct core concept but with significant errors: 40-60% of max_score\\n- Partially relevant but mostly incorrect: 10-30% of max_score\\n- Completely incorrect or irrelevant: 0 points\\n\\nGLOBAL SCORING NOTES:\\n- Always evaluate responses based on semantic similarity to the perfect response\\n- Consider synonyms, alternative phrasings, and different levels of detail\\n- Account for spelling mistakes and typos if the intent is clear\\n- Give partial credit for responses that contain some correct elements\\n- Avoid binary (correct/incorrect) scoring - use the full scoring range\\n- Reward comprehensive answers that include additional accurate details\\n- Penalize responses with factual errors but give credit for correct portions\\n- Be consistent in applying scoring criteria across all responses\\n\\nIMPORTANT INSTRUCTIONS:\\n1. Use nuanced scoring - not just 0 or max_score\\n2. Evaluate factual accuracy and completeness\\n3. Consider the quality and depth of the response\\n4. You MUST return ONLY a valid JSON object with no markdown formatting, no code blocks, no explanations, and no additional text\\n5. The JSON must be parseable and contain only the 'user_responses_audited' key\\n\\nRETURN FORMAT: Return only this exact JSON structure:\\n{\\n  \\\"user_responses_audited\\\": [\\n    {\\n      \\\"user_id\\\": number,\\n      \\\"user_response_score\\\": number\\n    }\\n  ]\\n}\",
  \"question_details\": {
    \"question_text\": \"Who invented the telephone?\",
    \"max_score\": 10,
    \"perfect_response\": \"Alexander Graham Bell invented the telephone in 1876.\"
  },
  \"user_responses\": [
    {
      \"user_id\": 101,
      \"user_response\": \"Alexander Graham Bell invented the telephone in 1876. He was a Scottish-American inventor who received the first patent for the telephone.\"
    },
    {
      \"user_id\": 102,
      \"user_response\": \"Thomas Edison invented the telephone.\"
    },
    {
      \"user_id\": 103,
      \"user_response\": \"Alexander Graham Bell invented it, but I think Antonio Meucci also had something to do with early telephone development.\"
    }
      .... rest of users in batch
  ],
  \"response_format\": {
    \"user_responses_audited\": [
      {
        \"user_id\": \"number\",
        \"user_response_score\": \"number\"
      }
    ]
  }
}";
        
        // Generate the question
        $response = $geminiService->generate($prompt);
        
        if ($response->isSuccess()) {
            $question = $response->getContent();
            $tokens = $response->getTokensUsed();
            $cost = $response->getCost();
            
            return view('test', [
                'question' => $question,
                'tokens' => $tokens,
                'cost' => $cost,
                'success' => true
            ]);
        } else {
            return view('test', [
                'error' => $response->getError(),
                'success' => false
            ]);
        }
        
    } catch (\Exception $e) {
        return view('test', [
            'error' => 'Exception: ' . $e->getMessage(),
            'success' => false
        ]);
    }
});


Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){

    Route::get('/', function () {
        return view('welcome');

    })->name('home');

    Route::middleware('auth')->name("user.")->group(function () {

        Route::get('/dashboard', [UserController::class, 'index'])->name('index');

        Route::get('/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [UserProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserProfileController::class, 'destroy'])->name('profile.destroy');

        Route::middleware('auth_competitor')->group(function (){
            Route::get( '/user/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "getUserCompetitions"])->name('competitions');
            Route::post( '/user/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "getUserCompetitions"])->name('competitions.filtred');
            Route::get('/competition/{level}/response', [\App\Http\Controllers\User\UserCompetitionController::class, 'beginUserLevelAttempt'])->name('competitions.level.response');
            Route::post('/competition/{question}/response/store', [\App\Http\Controllers\User\UserCompetitionController::class, 'storeResponse'])->name('competitions.level.response.store');
            Route::get('/competition/user/{level}/responses', [\App\Http\Controllers\User\UserCompetitionController::class, 'userResponses'])->name('competitions.response');
            Route::post('/record-tab-switch', function () {
                session(['tab_switched' => true]);
                return response()->json(['ok' => true]);
            });
        });
        // global questions
        Route::get('/questions/response/', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getRandomQuestion'])->name('global_questions.response');
        Route::get('/questions/response/ai/{questionId?}', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getRandomAIQuestion'])->name('global_questions.response.ai');
        Route::get('/questions/response/premium', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getRandomPremiumQuestion'])->name('global_questions.response.premium');
        Route::post('/questions/response/store', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'storeResponse'])->name('global_questions.response.store');

        Route::get('my/global-responses', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getGlobalUserResponse'])->name('global_questions.responses');

        Route::get('/ai-question-generation', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'aiQuestionGeneration'])->name('global_questions.ai_question_generation');
        Route::post('/ai-question-generation', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'generateAIQuestion'])->name('global_questions.ai_question_generation.store');
        
        Route::get('/premium-info', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'premiumInfo'])->name('global_questions.premium_info');
        
        Route::get('/premium-questions', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getRandomPremiumQuestion'])->name('global_questions.premium.browse');
    });

    Route::get( '/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "getAllPublicCompetitions"])->name('competitions');
    Route::Post( '/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "getAllPublicCompetitions"])->name('competitions.filtred');


    Route::get('/competition/{competition}', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitionDetail'])->name('competitions.detail');
    Route::get('/competition/{competition}/competitors-order', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitorsCompetitionOrder'])->name('competitions.order');
    Route::get('/competition/level/{level}', [\App\Http\Controllers\User\UserCompetitionController::class, 'levelDetail'])->name('competitions.level');
    Route::get('/competition/level/{level}/competitors-order', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitorsLevelOrder'])->name('competitions.level.order');

    // global questions
    Route::get('/questions',[\App\Http\Controllers\GuestUsers\UserGuestController::class, 'index'])->name('global_questions.index');
    Route::get('/global-order', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'globalUsersOrder'])->name('global_questions.global_order');

    
    // Payment routes for users
    Route::middleware('either.auth')->group(function () {
        Route::middleware('not_allowed_roles:accountant,admin')->group(function () {
            Route::get('/payment/create', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'create'])->name('payment.create');
            Route::post('/payment/store', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'store'])->name('payment.store');
            Route::get('/payment/transactions', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'index'])->name('payment.transactions');
            Route::get('/payment/transactions/{paymentTransaction}', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'show'])->name('payment.transactions.show');
            Route::get('/payment/coin-balance', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'getCoinBalance'])->name('payment.coin-balance');
            Route::get('/payment/history', [\App\Http\Controllers\Payment\CoinTransactionController::class, 'index'])->name('payment.transactions.history');
        
        Route::post('/payment/reviews/order', [\App\Http\Controllers\Payment\PaymentTransactionController::class, 'orderReview'])->name('payment.reviews.order');
        });
        Route::get('/transactions/{transaction}/proof', [PaymentProofController::class, 'show'])
        ->name('transactions.proof');
    });

    // Include notification routes inside localization middleware
    require __DIR__.'/notification.php';

    require __DIR__.'/auth.php';
});

require base_path('/routes/admin.php');
