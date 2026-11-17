<?php

namespace App\Http\Controllers;

use App\Models\LearnerVocabularyItem;
use App\Services\Learning\VocabularyReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VocabularyReviewController extends Controller
{
    public function index(VocabularyReviewService $reviews): View
    {
        $user = auth()->user();
        $dueItems = $reviews->dueItems($user);
        $upcomingItems = $reviews->upcomingItems($user);
        $dueCount = $user->vocabularyItems()->where('due_at', '<=', now())->count();

        return view('pages.dashboard.reviews', compact('dueItems', 'upcomingItems', 'dueCount'));
    }

    public function answer(Request $request, LearnerVocabularyItem $item, VocabularyReviewService $reviews): RedirectResponse
    {
        $data = $request->validate([
            'grade' => ['required', 'in:again,hard,good,easy'],
            'response_time_ms' => ['nullable', 'integer', 'min:0', 'max:600000'],
        ]);

        $reviews->review(auth()->user(), $item, $data['grade'], $data['response_time_ms'] ?? null);

        return redirect()->route('reviews.index')->with('reviewed', true);
    }
}
