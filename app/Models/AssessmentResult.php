<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentResult extends Model {
    protected $fillable = ['test_attempt_id','overall_score','overall_percentage','level','grammar_score','vocabulary_score','reading_score','writing_score','listening_score','speaking_score','recommendation'];
    public function attempt(){ return $this->belongsTo(TestAttempt::class,'test_attempt_id'); }
}
