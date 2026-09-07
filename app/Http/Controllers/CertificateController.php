<?php
namespace App\Http\Controllers;

use App\Models\{Test, TestAttempt};
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller {
    public function download(Test $test, TestAttempt $attempt) {
        abort_unless($attempt->test_id === $test->id, 404);
        abort_unless($attempt->status === 'completed', 404);
        // authz: owner (or anon session)
        if ($attempt->user_id) abort_unless(auth()->id() === $attempt->user_id, 403);
        else abort_unless(session('anon_session') === $attempt->session_id, 403);

        $attempt->load('result','user','test.level');
        $name = $attempt->user?->name ?: 'Anonymous learner';
        $data = [
            'attempt' => $attempt,
            'result' => $attempt->result,
            'test' => $test,
            'name' => $name,
            'issued' => now()->format('F j, Y'),
            'certificate_id' => 'ENG-'.str_pad((string)$attempt->id, 6, '0', STR_PAD_LEFT),
        ];
        $pdf = Pdf::loadView('pdf.certificate', $data)->setPaper('a4','landscape');
        return $pdf->download('engleza-wiki-certificate-'.$attempt->id.'.pdf');
    }
}
