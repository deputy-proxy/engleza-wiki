<?php

namespace App\Http\Controllers;

use App\Models\PrivacyDataRequest;
use App\Services\Privacy\PersonalDataExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function policy(string $policy): View
    {
        abort_unless(in_array($policy, ['privacy', 'terms', 'cookies'], true), 404);

        return view('pages.policies.'.$policy, [
            'policyVersion' => config('privacy.policy_version'),
            'policyStatus' => config('privacy.policy_status'),
        ]);
    }

    public function contact(): View
    {
        return view('pages.privacy.contact', [
            'contactEmail' => config('privacy.contact_email'),
        ]);
    }

    public function requests(Request $request): View
    {
        return view('pages.privacy.requests', [
            'requests' => $request->user()->privacyDataRequests()->latest()->paginate(10),
        ]);
    }

    public function storeRequest(Request $request): RedirectResponse|StreamedResponse
    {
        $data = $request->validate([
            'request_type' => ['required', Rule::in(['access', 'correction', 'deletion'])],
            'description' => ['nullable', 'string', 'max:5000', 'required_if:request_type,correction,deletion'],
            'current_password' => ['required', 'current_password'],
        ]);

        abort_unless($request->user()->hasVerifiedEmail(), 403);

        if ($data['request_type'] === 'access') {
            $payload = app(PersonalDataExport::class)->for($request->user());
            $content = json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );

            PrivacyDataRequest::create([
                'user_id' => $request->user()->getKey(),
                'request_type' => 'access',
                'status' => 'completed',
                'identity_verified_at' => now(),
                'completed_at' => now(),
            ]);

            return response()->streamDownload(
                static function () use ($content): void {
                    echo $content;
                },
                'engleza-wiki-personal-data.json',
                ['Content-Type' => 'application/json; charset=UTF-8'],
            );
        }

        PrivacyDataRequest::create([
            'user_id' => $request->user()->getKey(),
            'request_type' => $data['request_type'],
            'status' => 'submitted',
            'description' => $data['description'],
            'identity_verified_at' => now(),
        ]);

        return redirect()->route('privacy.requests')->with('status', __('Solicitarea ta a fost înregistrată. Poți urmări starea aici.'));
    }

    public function cookieSettings(Request $request): View
    {
        $rawPreferences = $request->cookie('engleza_cookie_preferences', '{}');
        $decodedPreferences = is_string($rawPreferences) ? json_decode($rawPreferences, true) : [];
        $preferences = is_array($decodedPreferences) ? $decodedPreferences : [];

        return view('pages.privacy.cookies-settings', [
            'analytics' => (bool) ($preferences['analytics'] ?? false),
        ]);
    }

    public function saveCookieSettings(Request $request): RedirectResponse
    {
        $data = $request->validate(['analytics' => ['required', 'boolean']]);
        $value = json_encode([
            'analytics' => (bool) $data['analytics'],
            'updated_at' => now()->toIso8601String(),
            'version' => config('privacy.cookie_policy_version'),
        ], JSON_THROW_ON_ERROR);

        Cookie::queue(cookie(
            'engleza_cookie_preferences',
            $value,
            60 * 24 * 180,
            '/',
            null,
            (bool) config('session.secure'),
            true,
            false,
            'lax',
        ));

        return back()->with('status', __('Preferințele cookie au fost salvate.'));
    }

    public function adminRequests(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return view('pages.privacy.admin-requests', [
            'requests' => PrivacyDataRequest::query()->with('user')->latest()->paginate(25),
        ]);
    }

    public function updateAdminRequest(Request $request, PrivacyDataRequest $privacyRequest): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['submitted', 'in_review', 'completed', 'rejected'])],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $privacyRequest->update([
            ...$data,
            'reviewed_by' => $request->user()->getKey(),
            'completed_at' => in_array($data['status'], ['completed', 'rejected'], true) ? now() : null,
        ]);

        return back()->with('status', __('Solicitarea privind datele a fost actualizată.'));
    }
}
