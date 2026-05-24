<?php

use App\Http\Controllers\Auth\VkController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\QuickLogin;
use App\Livewire\Dashboard;
use App\Livewire\Platforms\Index as PlatformsIndex;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Livewire\Answers\Index as AnswersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', Login::class)->name('login');
Route::get('/quick-login', QuickLogin::class)->name('quick.login');
Route::get('/dashboard', Dashboard::class)->middleware('auth')->name('dashboard');
Route::get('/platforms', PlatformsIndex::class)->middleware('auth')->name('platforms');
Route::get('/messages', MessagesIndex::class)->middleware('auth')->name('messages');
Route::get('/processing', AnswersIndex::class)->middleware('auth')->name('answers');

Route::prefix('auth/vk')->name('vk.')->group(function () {
    Route::get('redirect', [VkController::class, 'redirect'])->name('redirect');
    Route::get('callback', [VkController::class, 'callback'])->name('callback');
});

Route::post('logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');
