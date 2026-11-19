<?php

use App\Http\Controllers\Admin\AppUpdateController;
use Illuminate\Support\Facades\Route;

// Owner app update reminders (admin). Loaded from routes/web.php.
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/app-updates', [AppUpdateController::class, 'index'])->name('app-updates');
    Route::post('/app-updates', [AppUpdateController::class, 'save'])->name('app-updates.save');
});
