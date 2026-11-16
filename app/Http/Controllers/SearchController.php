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
                $q->where('title', 'like', "%$term%")->orWhere('excerpt', 'like', "%$term%")->orWhere('content', 'like', "%$term%");
            })->latest('published_at')->limit(30)->get();
            $tests = Test::published()->where('title', 'like', "%$term%")->limit(10)->get();
        }

        return view('pages.search', compact('term', 'resources', 'tests'));
    }
}
