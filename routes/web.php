<?php

use App\Http\Controllers\User\UserProfileController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/', function () {
    return \App\Models\User::ageBetween(8, 20)->get();
   // return view('welcome');
    //return "hi";
});

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){

    Route::middleware('auth')->name("user.")->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('index');
        Route::get('/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [UserProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [UserProfileController::class, 'destroy'])->name('profile.destroy');
    });
    Route::match(['get', 'post'], '/competitions', [\App\Http\Controllers\User\UserCompetitionController::class, "all"])->name('competitions');

});



require __DIR__.'/auth.php';

require base_path('/routes/admin.php');
