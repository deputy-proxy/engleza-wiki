<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Resource;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        $category->load('translations');
        $resources = Resource::published()->with(['translations', 'level.translations', 'skills.translations'])->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->latest('published_at')->paginate(12);

        return view('pages.categories.show', compact('category', 'resources'));
    }
}
