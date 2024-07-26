<?php
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
|
*/
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => [ 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){
    Livewire::setUpdateRoute(function ($handle) {
        return Route::post('/livewire/update', $handle);
    });
    Route::prefix("admin")->name("admin.")->group(function () {
        Route::middleware('auth:admin')->group(function () {

            Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, "index"])->name("index");
            Route::get('/admins/', [\App\Http\Controllers\Admin\AdminController::class, "getAdminList"])->name("list");
            Route::group(['middleware' => ['role:owner|super_admin']], function (){
                Route::post('/store', [\App\Http\Controllers\Admin\AdminController::class, "store"])->name("store");
                Route::middleware('auth_user_profile')->get('/admins/edit/{id}', [\App\Http\Controllers\Admin\AdminController::class, "edit"])->name("edit");
                Route::patch('/update', [\App\Http\Controllers\Admin\AdminController::class, "update"])->name("update");
                Route::post('/delete', [\App\Http\Controllers\Admin\AdminController::class, "delete"])->middleware("can_delete_admin")->name("delete");

                Route::get('/activity', [\App\Http\Controllers\Admin\AdminController::class, "showActivity"])->name("activity");

            });

            // users manipulation
            Route::get('/users', [\App\Http\Controllers\User\UserController::class, "show"])->name("users");
            Route::post('/users/store', [\App\Http\Controllers\User\UserController::class, "store"])->name("users.store");
            Route::middleware('can:update user')->get('/users/edit/{id}', [\App\Http\Controllers\User\UserController::class, "edit"])->name("users.edit");
            Route::patch('/users/update', [\App\Http\Controllers\User\UserController::class, "update"])->name("users.update");
            Route::post('/users/delete', [\App\Http\Controllers\User\UserController::class, "delete"])->middleware("can_delete_user")->name("users.delete");

            Route::get('/profile', [\App\Http\Controllers\Admin\AdminProfileController::class, 'edit'])->name('profile.edit');
           // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
           // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        });

        require __DIR__.'/auth_admin.php';
    });

});



