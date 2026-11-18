<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LearningPath;
use App\Models\Resource;
use App\Models\Test;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('sitemap', [
            'staticUrls' => [
                '/', '/resources', '/learning', '/tests', '/about',
            ],
            'resources' => Resource::published()->where('robots_index', true)->orderBy('id')->get(),
            'tests' => Test::published()->where('is_public', true)->orderBy('id')->get(),
            'categories' => Category::query()->where('is_active', true)->orderBy('id')->get(),
            'learningPaths' => LearningPath::published()->orderBy('id')->get(),
        ], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
