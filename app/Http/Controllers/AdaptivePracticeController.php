<?php

namespace App\Http\Controllers;

use App\Models\AdaptivePracticeSession;
use App\Models\ExperiencePointAward;
use App\Services\Learning\AdaptivePracticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdaptivePracticeController extends Controller
{
    public function start(AdaptivePracticeService $practice): RedirectResponse
    {
        $session = $practice->start(auth()->user());

        return redirect()->route('practice.show', $session);
    }

    public function show(AdaptivePracticeSession $session, AdaptivePracticeService $practice): View
    {
        $session = $practice->session($session, auth()->user());
        $questions = $practice->questionPayload($session);
        $xpAwarded = (int) ExperiencePointAward::query()->where('user_id', auth()->id())
            ->where('source_type', 'adaptive_practice')->where('source_id', $session->id)->value('points');

        return view('pages.dashboard.practice', compact('session', 'questions', 'xpAwarded'));
    }

    public function submit(Request $request, AdaptivePracticeSession $session, AdaptivePracticeService $practice): RedirectResponse
    {
        $session = $practice->session($session, auth()->user());
        abort_unless($session->status === 'started', 410);
        $data = $request->validate(['answers' => ['sometimes', 'array']]);

        DB::transaction(function () use ($data, $session, $practice): void {
            foreach ($data['answers'] ?? [] as $questionId => $response) {
                if (is_string($response) && in_array((int) $questionId, $session->questionIds(), true)) {
                    $decoded = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $response = $decoded;
                    }
                }

                if (! $practice->recordResponse($session, auth()->user(), (int) $questionId, $response)) {
                    throw ValidationException::withMessages([
                        'answers.'.$questionId => __('This answer could not be validated. Check its format and try again.'),
                    ]);
                }
            }

            $practice->complete($session, auth()->user());
        });

        return redirect()->route('practice.show', $session);
    }
}
