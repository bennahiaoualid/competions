<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;

Route::middleware(['either.auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/delete', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('notifications.bulkDelete');

    Route::get('/notifications/get-latest-notification-json', [NotificationController::class, 'getLatestNotificationJson'])->name('notifications.getLatestNotificationJson');
}); 
//Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
