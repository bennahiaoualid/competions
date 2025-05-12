<?php

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserProfileController;
use App\Mail\UserNotification;
use App\Models\Competition\Competition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
Route::get('/test',function (){
    dd(Auth::check());
      /*  $data = [
            'subject' => 'notify',
            'user' => "ddd",
            'competition' => "dd",
            'type' => 'new_competition',
            'object'=> 'competition',
        ];
        Mail::to('oualidbennahia@gmail.com')->send(new UserNotification($data,'ar'));*/
    /*$competition = Competition::find(12);
    foreach ($competition->users as $user) {
        $data = [
            'subject' => 'notify',
            'user' => $user->name,
            'competition' => $competition->title,
            'type' => 'new_competition',
            'object'=> 'competition',
            'link' => route('competitions.detail',['id'=>base64_encode($competition->id)])
        ];
        //Mail::to($user->email)->send(new UserNotification($data,'ar'));
        Mail::to($user->email)->queue(new UserNotification($data,'en'));
    }*/
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

        Route::get( '/competitions/{user}', [\App\Http\Controllers\User\UserCompetitionController::class, "all"])->name('competitions');
        Route::post( '/competitions/{user}', [\App\Http\Controllers\User\UserCompetitionController::class, "all"])->name('competitions');

        Route::middleware('auth_competitor')->group(function (){
            Route::get('/competition/response/{id}', [\App\Http\Controllers\User\UserCompetitionController::class, 'levelStart'])->name('competitions.level.response');
            Route::post('/competition/response/store', [\App\Http\Controllers\User\UserCompetitionController::class, 'storeResponse'])->name('competitions.level.response.store');
            Route::get('/competition/user/responses/{id}', [\App\Http\Controllers\User\UserCompetitionController::class, 'userResponses'])->name('competitions.response');

        });
        // global questions
        Route::get('/questions/response/', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getRandomQuestion'])->name('global_questions.response');
        Route::post('/questions/response/store', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'storeResponse'])->name('global_questions.response.store');

        Route::get('my/global-responses', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'getGlobalUserResponse'])->name('global_questions.responses');

    });

    Route::get( '/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "all"])->name('competitions');
    Route::Post( '/competitions/{user?}', [\App\Http\Controllers\User\UserCompetitionController::class, "all"])->name('competitions');


    Route::get('/competition/{id}', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitionDetail'])->name('competitions.detail');
    Route::get('/competition/{id}/competitors-order', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitorsCompetitionOrder'])->name('competitions.order');
    Route::get('/competition/level/{id}', [\App\Http\Controllers\User\UserCompetitionController::class, 'levelDetail'])->name('competitions.level');
    Route::get('/competition/level/{id}/competitors-order', [\App\Http\Controllers\User\UserCompetitionController::class, 'competitorsLevelOrder'])->name('competitions.level.order');

    // global questions
    Route::get('/questions/',[\App\Http\Controllers\GuestUsers\UserGuestController::class, 'index'])->name('global_questions.index');
    Route::get('/global-order', [\App\Http\Controllers\GuestUsers\UserGuestController::class, 'globalUsersOrder'])->name('global_questions.global_order');


    require __DIR__.'/auth.php';

});




require base_path('/routes/admin.php');
