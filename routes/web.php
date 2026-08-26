<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{HomeController, ResourceController, CategoryController, SearchController, TestController, DashboardController};
use App\Http\Controllers\Auth\AuthController;

Route::get('/', [HomeController::class,'index'])->name('home');
Route::get('/resources', [ResourceController::class,'index'])->name('resources.index');
Route::get('/resources/{resource:slug}', [ResourceController::class,'show'])->name('resources.show');
Route::get('/categories/{category:slug}', [CategoryController::class,'show'])->name('categories.show');
Route::get('/search', [SearchController::class,'index'])->name('search');

// Semantic aliases
Route::get('/for-teachers', function(){ return redirect()->route('resources.index',['audience'=>'teachers']); })->name('for-teachers');
Route::get('/for-learners', function(){ return redirect()->route('resources.index',['audience'=>'learners']); })->name('for-learners');
Route::get('/grammar', function(){ return redirect()->route('resources.index',['skill'=>'grammar']); })->name('grammar');
Route::get('/vocabulary', function(){ return redirect()->route('resources.index',['skill'=>'vocabulary']); })->name('vocabulary');
Route::view('/about','pages.about')->name('about');

Route::get('/tests', [TestController::class,'index'])->name('tests.index');
Route::get('/tests/{test:slug}', [TestController::class,'show'])->name('tests.show');
Route::middleware('throttle:20,1')->post('/tests/{test:slug}/start', [TestController::class,'start'])->name('tests.start');
Route::get('/tests/{test:slug}/take/{attempt}', [TestController::class,'take'])->name('tests.take');
Route::get('/tests/{test:slug}/result/{attempt}', [TestController::class,'result'])->name('tests.result');

Route::middleware('guest')->group(function(){
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login'])->middleware('throttle:5,1');
    Route::get('/register',[AuthController::class,'showRegister'])->name('register');
    Route::post('/register',[AuthController::class,'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function(){
    Route::post('/logout',[AuthController::class,'logout'])->name('logout');
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/my-tests',[DashboardController::class,'tests'])->name('dashboard.tests');
    Route::get('/my-resources',[DashboardController::class,'resources'])->name('dashboard.resources');
    Route::post('/resources/{resource:slug}/save',[DashboardController::class,'toggleSave'])->name('resources.toggle-save');
});
