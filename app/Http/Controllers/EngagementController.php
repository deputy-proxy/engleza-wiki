<?php

namespace App\Http\Controllers;

use App\Services\Learning\EngagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EngagementController extends Controller
{
    public function updateGoal(Request $request, EngagementService $engagement): RedirectResponse
    {
        $data = $request->validate([
            'daily_goal_xp' => ['required', 'integer', Rule::in([10, 20, 30, 50])],
            'timezone' => ['required', 'timezone'],
        ]);

        $engagement->updateSettings($request->user(), (int) $data['daily_goal_xp'], $data['timezone']);

        return redirect()->route('dashboard')->with('engagement_settings_updated', true);
    }
}
