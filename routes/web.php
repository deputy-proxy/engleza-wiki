<?php

use App\Http\Controllers\AdaptivePracticeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ClassGroupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\VocabularyReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
Route::get('/resources/{resource:slug}', [ResourceController::class, 'show'])->name('resources.show');

Route::get('/learning', [LearningPathController::class, 'index'])->name('learning.paths.index');
Route::get('/learning/{path:slug}', [LearningPathController::class, 'show'])->name('learning.paths.show');
Route::middleware('auth')->group(function (): void {
    Route::post('/learning/{path:slug}/enroll', [LearningPathController::class, 'enroll'])->name('learning.paths.enroll');
    Route::get('/learning/lessons/{lesson:slug}', [LearningPathController::class, 'lesson'])->name('learning.lessons.show');
    Route::post('/learning/activities/{activity}/complete', [LearningPathController::class, 'completeActivity'])->name('learning.activities.complete');
    Route::post('/learning/lessons/{lesson:slug}/complete', [LearningPathController::class, 'completeLesson'])->name('learning.lessons.complete');
    Route::get('/my-learning', [LearningPathController::class, 'myLearning'])->name('dashboard.learning');
    Route::get('/reviews', [VocabularyReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{item}/answer', [VocabularyReviewController::class, 'answer'])->name('reviews.answer')->middleware('throttle:60,1');
    Route::post('/practice/start', [AdaptivePracticeController::class, 'start'])->name('practice.start');
    Route::get('/practice/{session}', [AdaptivePracticeController::class, 'show'])->name('practice.show');
    Route::post('/practice/{session}/submit', [AdaptivePracticeController::class, 'submit'])->name('practice.submit')->middleware('throttle:30,1');
});

Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Semantic aliases
Route::get('/for-teachers', function () {
    return redirect()->route('resources.index', ['audience' => 'teachers']);
})->name('for-teachers');
Route::get('/for-learners', function () {
    return redirect()->route('resources.index', ['audience' => 'learners']);
})->name('for-learners');
Route::get('/grammar', function () {
    return redirect()->route('resources.index', ['skill' => 'grammar']);
})->name('grammar');
Route::get('/vocabulary', function () {
    return redirect()->route('resources.index', ['skill' => 'vocabulary']);
})->name('vocabulary');
Route::view('/about', 'pages.about')->name('about');

Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
Route::get('/tests/{test:slug}', [TestController::class, 'show'])->name('tests.show');
Route::middleware('throttle:20,1')->post('/tests/{test:slug}/start', [TestController::class, 'start'])->name('tests.start');
Route::get('/tests/{test:slug}/take/{attempt}', [TestController::class, 'take'])->name('tests.take');
Route::get('/tests/{test:slug}/result/{attempt}', [TestController::class, 'result'])->name('tests.result');
Route::get('/tests/{test:slug}/certificate/{attempt}', [CertificateController::class, 'download'])->name('tests.certificate');

// Teacher class join codes (public join, teacher-only results)
Route::get('/classes/{code}', [ClassGroupController::class, 'show'])->name('classes.show');
Route::get('/classes/{code}/results', [ClassGroupController::class, 'results'])->name('classes.results')->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::get('/password/forgot', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::put('/engagement/goal', [EngagementController::class, 'updateGoal'])->name('engagement.goal.update')->middleware('throttle:10,1');
    Route::get('/my-tests', [DashboardController::class, 'tests'])->name('dashboard.tests');
    Route::get('/my-resources', [DashboardController::class, 'resources'])->name('dashboard.resources');
    Route::get('/my-writing', [DashboardController::class, 'writing'])->name('dashboard.writing');
    Route::post('/resources/{resource:slug}/save', [DashboardController::class, 'toggleSave'])->name('resources.toggle-save');
    Route::post('/resources/{resource:slug}/writing/submit', [LearningController::class, 'submitWriting'])->name('resources.writing.submit')->middleware('throttle:30,1');
});

// Learning attempts (listening/reading) — anonymous allowed
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/resources/{resource:slug}/attempt/start', [LearningController::class, 'start'])->name('resources.attempt.start');
    Route::post('/resources/{resource:slug}/attempt/submit', [LearningController::class, 'submit'])->name('resources.attempt.submit');
});
Route::get('/resources/{resource:slug}/result/{attempt}', [LearningController::class, 'result'])->name('resources.result');

Route::get('/privacy-policy', [PrivacyController::class, 'policy'])->defaults('policy', 'privacy')->name('privacy.policy');
Route::get('/terms', [PrivacyController::class, 'policy'])->defaults('policy', 'terms')->name('privacy.terms');
Route::get('/cookie-policy', [PrivacyController::class, 'policy'])->defaults('policy', 'cookies')->name('privacy.cookies');
Route::get('/privacy/contact', [PrivacyController::class, 'contact'])->name('privacy.contact');
Route::get('/privacy/cookie-settings', [PrivacyController::class, 'cookieSettings'])->name('privacy.cookie-settings');
Route::post('/privacy/cookie-settings', [PrivacyController::class, 'saveCookieSettings'])->name('privacy.cookie-settings.save')->middleware('throttle:20,1');

Route::middleware(['auth', 'verified'])->prefix('privacy')->name('privacy.')->group(function (): void {
    Route::get('/requests', [PrivacyController::class, 'requests'])->name('requests');
    Route::post('/requests', [PrivacyController::class, 'storeRequest'])->name('requests.store')->middleware('throttle:5,1');
});

Route::middleware(['auth', 'verified'])->prefix('admin/privacy')->name('admin.privacy.')->group(function (): void {
    Route::get('/requests', [PrivacyController::class, 'adminRequests'])->name('requests');
    Route::put('/requests/{privacyRequest}', [PrivacyController::class, 'updateAdminRequest'])->name('requests.update')->middleware('throttle:20,1');
});
