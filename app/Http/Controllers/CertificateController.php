<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Test\TestAttemptService;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function download(Test $test, TestAttempt $attempt, TestAttemptService $attempts)
    {
        abort_unless($attempt->test_id === $test->id, 404);
        abort_unless($attempt->status === 'completed', 404);
        $attempts->authorize($attempt, auth()->id(), session('anon_session'));

        $attempt->load('result', 'user', 'test.level');
        $name = $attempt->user?->name ?: 'Anonymous learner';
        $data = [
            'attempt' => $attempt,
            'result' => $attempt->result,
            'test' => $test,
            'name' => $name,
            'issued' => now()->translatedFormat('j F Y'),
            'certificate_id' => 'ENG-'.str_pad((string) $attempt->id, 6, '0', STR_PAD_LEFT),
        ];
        $pdf = Pdf::loadView('pdf.certificate', $data)->setPaper('a4', 'landscape');

        return $pdf->download('engleza-wiki-certificate-'.$attempt->id.'.pdf');
    }
}
