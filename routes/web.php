<?php

use App\Models\User;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\Payment\PaymentProofController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
Route::get('/test-ui', function () {
  $user = User::factory()->make([
    'id' => 999,
    'name' => 'Fake Dev User',
    'email' => 'fake@example.com',
]);

// Inject into Auth
Auth::setUser($user);
  $level = Level::factory()->make();
  $question = Question::factory()->for($level)->make(['id' => 1]);
    $data = [
      'status' => 'success',
      'question' => $question,
      'question_count' => [
        'current' =>  1,
        'all' => $level->questions_number
    ],
      'level_name' => $level->name,
      'competition_title' => $level->competition->title
  ];
  return view('pages.user.question_response', $data);

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
