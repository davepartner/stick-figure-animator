<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('videos.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Video Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::post('/videos/estimate-cost', [VideoController::class, 'estimateCost'])->name('videos.estimate-cost');
    Route::post('/videos', [VideoController::class, 'store'])->name('videos.store');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('videos.show');
    Route::get('/videos/{id}/status', [VideoController::class, 'checkStatus'])->name('videos.check-status');
    Route::get('/videos/{id}/download', [VideoController::class, 'download'])->name('videos.download');
    Route::post('/videos/{id}/regenerate', [VideoController::class, 'regenerate'])->name('videos.regenerate');
    Route::post('/videos/{id}/youtube-content', [VideoController::class, 'generateYouTubeContent'])->name('videos.youtube-content');
});

// Payment Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/credits', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/stripe/checkout', [PaymentController::class, 'createStripeCheckout'])->name('payments.stripe-checkout');
    Route::get('/payments/stripe/success', [PaymentController::class, 'stripeSuccess'])->name('payments.stripe-success');
    Route::post('/payments/paystack/payment', [PaymentController::class, 'createPaystackPayment'])->name('payments.paystack-payment');
    Route::get('/payments/paystack/callback', [PaymentController::class, 'paystackCallback'])->name('payments.paystack-callback');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/credits', [AdminController::class, 'updateUserCredits'])->name('users.update-credits');
    Route::get('/videos', [AdminController::class, 'videos'])->name('videos');
});

require __DIR__.'/auth.php';
