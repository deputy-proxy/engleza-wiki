<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Resource;
use App\Models\Skill;
use App\Services\Learning\LearningPathService;
use App\Services\Learning\VocabularyReviewService;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $r)
    {
        $q = Resource::published()->with(['level', 'skills', 'categories']);
        if ($r->filled('type')) {
            $q->where('type', $r->type);
        }
        if ($r->filled('level')) {
            $q->whereHas('level', fn ($x) => $x->where('slug', $r->level));
        }
        if ($r->filled('skill')) {
            $q->whereHas('skills', fn ($x) => $x->where('slug', $r->skill));
        }
        if ($r->filled('audience')) {
            $q->whereHas('audiences', fn ($x) => $x->where('slug', $r->audience));
        }
        $resources = $q->latest('published_at')->paginate(12)->withQueryString();
        $levels = Level::orderBy('sort_order')->get();
        $skills = Skill::orderBy('name')->get();
        $types = ['article' => 'Articles', 'grammar' => 'Grammar', 'vocabulary' => 'Vocabulary', 'worksheet' => 'Worksheets', 'lesson_plan' => 'Lesson Plans', 'activity' => 'Activities', 'guide' => 'Guides', 'exercise' => 'Exercises', 'card' => 'Cards', 'listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing'];

        return view('pages.resources.index', compact('resources', 'levels', 'skills', 'types'));
    }

    public function show(Resource $resource, LearningPathService $learning, VocabularyReviewService $reviews)
    {
        abort_unless($resource->status === 'published', 404);
        $resource->load(['level', 'skills', 'categories', 'tags', 'topics', 'audiences', 'media', 'author']);
        $related = Resource::published()
            ->where('id', '!=', $resource->id)
            ->whereHas('topics', fn ($q) => $q->whereIn('topics.id', $resource->topics->pluck('id')))
            ->limit(4)->get();
        $view = match ($resource->type) {
            'card' => 'pages.resources.types.card',
            'listening' => 'pages.resources.types.listening',
            'reading' => 'pages.resources.types.reading',
            'writing' => 'pages.resources.types.writing',
            default => 'pages.resources.show',
        };
        // Eager-load type-specific children
        if ($resource->type === 'card') {
            $resource->load('cardResource.cards');
            if (auth()->check()) {
                $reviews->encounterResource(auth()->user(), $resource);
            }
        }
        if ($resource->type === 'listening') {
            $resource->load('listeningResource.questions.options');
        }
        if ($resource->type === 'reading') {
            $resource->load('readingResource.questions.options');
        }
        if (auth()->check() && in_array($resource->type, ['listening', 'reading'], true)) {
            $reviews->encounterResource(auth()->user(), $resource);
        }
        if ($resource->type === 'writing') {
            $resource->load('writingResource');
        }

        $learningActivities = $resource->lessonActivities()->with('lesson.unit.path')->get()
            ->filter(fn ($activity): bool => $learning->isLessonAvailable($activity->lesson));

        return view($view, compact('resource', 'related', 'learningActivities'));
    }
}
