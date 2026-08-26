<?php
namespace App\Http\Controllers;
use App\Models\{Category, Resource};

class CategoryController extends Controller {
    public function show(Category $category) {
        $resources = Resource::published()->whereHas('categories', fn($q)=>$q->where('categories.id',$category->id))
            ->latest('published_at')->paginate(12);
        return view('pages.categories.show', compact('category','resources'));
    }
}
