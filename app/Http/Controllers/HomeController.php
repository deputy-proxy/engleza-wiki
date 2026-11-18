<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Resource;
use App\Models\Test;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Resource::published()->with(['translations', 'level.translations', 'skills.translations'])->where('featured', true)->latest('published_at')->limit(6)->get();
        $popular = Resource::published()->with(['translations', 'level.translations', 'skills.translations'])->latest('published_at')->limit(6)->get();
        $categories = Category::with('translations')->whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->limit(6)->get();
        $tests = Test::published()->with(['translations', 'level.translations', 'skill.translations'])->where('is_public', true)->limit(4)->get();

        return view('pages.home', compact('featured', 'popular', 'categories', 'tests'));
    }
}
