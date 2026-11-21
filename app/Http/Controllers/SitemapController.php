<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LearningPath;
use App\Models\Resource;
use App\Models\Test;
use App\Services\Learning\LearningPathService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(LearningPathService $learning): Response
    {
        $canonicalBase = rtrim((string) config('app.url'), '/');

        $resources = Resource::published()
            ->where('editorial_status', 'approved')
            ->where('robots_index', true)
            ->orderBy('id')
            ->get()
            ->map(function (Resource $resource) use ($canonicalBase): ?Resource {
                $sitemapUrl = $this->normalizedLocalCanonical($resource, $canonicalBase);
                if ($sitemapUrl === null) {
                    return null;
                }

                $resource->setAttribute('sitemap_url', $sitemapUrl);

                return $resource;
            })
            ->filter()
            ->unique('sitemap_url')
            ->values();

        $tests = Test::published()
            ->where('is_public', true)
            ->where('editorial_status', 'approved')
            ->orderBy('id')
            ->get();

        $allPublicTests = Test::published()->where('is_public', true)->get();
        $testsListingIndexable = $allPublicTests->isNotEmpty()
            && $allPublicTests->every(fn (Test $test): bool => $test->editorial_status === 'approved');

        $categories = Category::query()
            ->where('is_active', true)
            ->with(['resources' => fn ($query) => $query->published()->orderByDesc('published_at')->limit(12)])
            ->orderBy('id')
            ->get()
            ->filter(function (Category $category) use ($canonicalBase): bool {
                $pageResources = $category->resources;

                return $pageResources->isNotEmpty() && $pageResources->every(
                    fn (Resource $resource): bool => $resource->editorial_status === 'approved'
                        && $resource->robots_index
                        && $this->normalizedLocalCanonical($resource, $canonicalBase) !== null,
                );
            })
            ->values();

        $availablePaths = $learning->availablePaths();
        $learningPaths = $availablePaths
            ->filter(fn ($path): bool => $this->areAllAvailableLessonsIndexable($path, $learning))
            ->values();

        $resourceListing = Resource::published()->orderByDesc('published_at')->limit(12)->get();
        $resourcesListingIndexable = $resourceListing->isNotEmpty() && $resourceListing->every(
            fn (Resource $resource): bool => $resource->editorial_status === 'approved'
                && $resource->robots_index
                && $this->normalizedLocalCanonical($resource, $canonicalBase) !== null,
        );

        $learningListingIndexable = $availablePaths->isNotEmpty()
            && $learningPaths->count() === $availablePaths->count();

        $staticUrls = ['/'];

        if ($resourcesListingIndexable) {
            $staticUrls[] = '/resources';
        }

        if ($learningListingIndexable) {
            $staticUrls[] = '/learning';
        }

        if ($testsListingIndexable) {
            $staticUrls[] = '/tests';
        }

        $staticUrls[] = '/about';

        if (config('privacy.policy_status') === 'published') {
            $staticUrls = [...$staticUrls, '/privacy-policy', '/terms', '/cookie-policy'];
        }

        return response()->view('sitemap', compact(
            'canonicalBase',
            'staticUrls',
            'resources',
            'tests',
            'categories',
            'learningPaths',
        ), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function areAllAvailableLessonsIndexable(LearningPath $path, LearningPathService $learning): bool
    {
        $availableLessons = $path->units
            ->where('is_published', true)
            ->flatMap(fn ($unit) => $unit->lessons->filter(fn ($lesson): bool => $learning->isLessonAvailable($lesson)));

        return $availableLessons->isNotEmpty()
            && $availableLessons->every(fn ($lesson): bool => $learning->isLessonIndexable($lesson));
    }

    private function normalizedLocalCanonical(Resource $resource, string $canonicalBase): ?string
    {
        $url = $resource->canonical_url ?: route('resources.show', $resource->slug, false);
        $canonicalHost = parse_url($canonicalBase, PHP_URL_HOST);
        $resourceHost = parse_url($url, PHP_URL_HOST) ?: $canonicalHost;

        if (! is_string($canonicalHost) || ! is_string($resourceHost)
            || (strcasecmp($resourceHost, $canonicalHost) !== 0 && strcasecmp($resourceHost, 'www.'.$canonicalHost) !== 0)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return $canonicalBase.($path === '/' ? '' : '/'.trim($path, '/'));
    }
}
