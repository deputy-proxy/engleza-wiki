<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Resource;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Category $category, Request $request)
    {
        $category->load('translations');
        $resources = Resource::published()->with(['translations', 'level.translations', 'skills.translations'])->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->latest('published_at')->paginate(12);
        $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $robotsIndex = $request->query() === [] && $resources->getCollection()->isNotEmpty()
            && $resources->getCollection()->every(function (Resource $resource) use ($canonicalHost): bool {
                if ($resource->editorial_status !== 'approved' || ! $resource->robots_index) {
                    return false;
                }

                if (blank($resource->canonical_url)) {
                    return true;
                }

                $resourceHost = parse_url($resource->canonical_url, PHP_URL_HOST);

                return is_string($canonicalHost) && is_string($resourceHost)
                    && (strcasecmp($resourceHost, $canonicalHost) === 0 || strcasecmp($resourceHost, 'www.'.$canonicalHost) === 0);
            });

        return view('pages.categories.show', compact('category', 'resources', 'robotsIndex'));
    }
}
