<?php
namespace App\Http\Controllers;

use App\Models\{Resource, Category, Test};

class HomeController extends Controller {
    public function index() {
        $featured = Resource::published()->where('featured',true)->latest('published_at')->limit(6)->get();
        $popular  = Resource::published()->latest('published_at')->limit(6)->get();
        $categories = Category::whereNull('parent_id')->where('is_active',true)->orderBy('sort_order')->limit(6)->get();
        $tests = Test::published()->where('is_public',true)->limit(4)->get();
        return view('pages.home', compact('featured','popular','categories','tests'));
    }
}
