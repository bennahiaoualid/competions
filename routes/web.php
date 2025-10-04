<?php

use App\Models\User;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Route;
use PharIo\Manifest\ElementCollection;
use App\Models\Competition\Competition;
use App\Http\Controllers\User\UserController;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Payment\PaymentProofController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){

    ////////////////////////////////// testint
    Route::get('/test-ui2', function () {
        $user = User::factory()->make([
            'id' => 999,
            'name' => 'Fake Dev User',
            'email' => 'fake@example.com',
        ]);
        $level = Level::factory()->make(['id' => 1,'start_date' => now()->subDay(),'status'=>'active']);
           // Fake questions for this level
        $questions = Question::factory()
        ->count(3)
        ->make()
        ->each(function ($q, $i) use ($level) {
            $q->id = $i + 1;
            $q->level_id = $level->id;
        });

        // Build fake responses
        $responses = $questions->map(function ($question, $i) use ($user) {
            $response = Response::factory()
                ->for($user)
                ->make([
                    'id' => $i + 1,
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                ]);

            // Attach the related question manually like with() does
            $response->setRelation('question', $question);

            return $response;
        });

           // ---- Fake Pagination ----
    $perPage = 3;
    $page = request()->get('page', 1);
    $paginated = new LengthAwarePaginator(
        $responses->forPage($page, $perPage), // slice items
        $responses->count(),                  // total items
        $perPage,                             // items per page
        $page,                                // current page
        ['path' => request()->url(), 'query' => request()->query()] // keep query params
    );

                
        
        // Inject into Auth
        Auth::setUser($user);
          $level = Level::factory()->make(['id' => 1,'start_date' => now()->subDay(),'status'=>'active']);
            $data = [
                'level' => $level,
            'responses' => $paginated,
        
            ];
          return view('pages.user.user_responses_list', $data);
        
        });

        Route::get('/test-ui', function () {
            $user = User::factory()->make([
                'id' => 999,
                'name' => 'Fake Dev User',
                'email' => 'fake@example.com',
            ]);
            
            $users = User::factory()->count(2)->make();
            $allUsers = collect([$user])->merge($users)->map(function ($user) {
                $user->total_score = 10;
                return $user;
            });
            
            // Inject into Auth
            Auth::setUser($user);
              $level = Level::factory()->make(['id' => 1,'start_date' => now()->subDay(),'status'=>'active']);
                $data = [
                    'level' => $level,
                    'users' => $allUsers,
                    'audit_finish' => false,
                    'userCanParticipate' => true
            
                ];
              return view('pages.user.level_detail', $data);
            
            });

    /////////////////////////////////// endf testing

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


    Route::get('/competition/{slug}', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitionDetail'])->name('competitions.detail');
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
