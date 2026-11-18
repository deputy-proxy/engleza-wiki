<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Test;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $r)
    {
        $term = trim((string) $r->get('q', ''));
        $resources = collect();
        $tests = collect();
        if ($term !== '') {
            $resources = Resource::published()->where(function ($q) use ($term) {
                $q->where('title', 'like', "%$term%")->orWhere('excerpt', 'like', "%$term%")->orWhere('content', 'like', "%$term%")->orWhereHas('translations', fn ($translations) => $translations->where('locale', app()->getLocale())->where('status', 'reviewed')->whereIn('field', ['title', 'excerpt', 'content'])->where('value', 'like', "%$term%"));
            })->latest('published_at')->limit(30)->get();
            $tests = Test::published()->where(fn ($query) => $query->where('title', 'like', "%$term%")->orWhereHas('translations', fn ($translations) => $translations->where('locale', app()->getLocale())->where('status', 'reviewed')->where('field', 'title')->where('value', 'like', "%$term%")))->limit(10)->get();
        }

        return view('pages.search', compact('term', 'resources', 'tests'));
    }
}
