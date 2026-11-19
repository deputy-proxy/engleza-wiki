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
        $q = Resource::published()->with(['translations', 'level.translations', 'skills.translations', 'categories.translations']);
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
        $levels = Level::with('translations')->orderBy('sort_order')->get();
        $skills = Skill::with('translations')->orderBy('name')->get();
        $types = ['article' => 'Articles', 'grammar' => 'Grammar', 'vocabulary' => 'Vocabulary', 'worksheet' => 'Worksheets', 'lesson_plan' => 'Lesson Plans', 'activity' => 'Activities', 'guide' => 'Guides', 'exercise' => 'Exercises', 'card' => 'Cards', 'listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing', 'speaking' => 'Speaking'];

        return view('pages.resources.index', compact('resources', 'levels', 'skills', 'types'));
    }

    public function show(Resource $resource, LearningPathService $learning, VocabularyReviewService $reviews)
    {
        abort_unless($resource->status === 'published' && $resource->isEditorialVisible(), 404);
        $resource->load(['translations', 'level.translations', 'skills.translations', 'categories.translations', 'tags.translations', 'topics.translations', 'audiences.translations', 'media', 'author']);
        $related = Resource::published()->with(['translations', 'level.translations'])
            ->where('id', '!=', $resource->id)
            ->whereHas('topics', fn ($q) => $q->whereIn('topics.id', $resource->topics->pluck('id')))
            ->limit(4)->get();
        $view = match ($resource->type) {
            'card' => 'pages.resources.types.card',
            'listening' => 'pages.resources.types.listening',
            'reading' => 'pages.resources.types.reading',
            'writing' => 'pages.resources.types.writing',
            'speaking' => 'pages.resources.types.speaking',
            default => 'pages.resources.show',
        };
        // Eager-load type-specific children
        if ($resource->type === 'card') {
            $resource->load(['cardResource.translations', 'cardResource.cards']);
            if (auth()->check()) {
                $reviews->encounterResource(auth()->user(), $resource);
            }
        }
        if ($resource->type === 'listening') {
            $resource->load(['listeningResource.translations', 'listeningResource.questions.translations', 'listeningResource.questions.options']);
        }
        if ($resource->type === 'reading') {
            $resource->load(['readingResource.translations', 'readingResource.questions.translations', 'readingResource.questions.options']);
        }
        if (auth()->check() && in_array($resource->type, ['listening', 'reading'], true)) {
            $reviews->encounterResource(auth()->user(), $resource);
        }
        if ($resource->type === 'writing') {
            $resource->load('writingResource.translations');
        }
        if ($resource->type === 'speaking') {
            abort_unless($resource->speakingResource !== null && $resource->speakingResource->publicationIssues() === [], 404);
            $resource->load('speakingResource.translations');
        }

        $learningActivities = $resource->lessonActivities()->with(['lesson.translations', 'lesson.unit.path.translations'])->get()
            ->filter(fn ($activity): bool => $learning->isLessonAvailable($activity->lesson));

        return view($view, compact('resource', 'related', 'learningActivities'));
    }
}
