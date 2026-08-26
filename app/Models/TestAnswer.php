<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TestAnswer extends Model {
    protected $fillable = ['test_attempt_id','question_id','question_option_id','answer_text','is_correct','points_awarded','answered_at'];
    protected $casts = ['answered_at'=>'datetime','is_correct'=>'bool'];
    public function attempt(){ return $this->belongsTo(TestAttempt::class,'test_attempt_id'); }
    public function question(){ return $this->belongsTo(Question::class); }
    public function option(){ return $this->belongsTo(QuestionOption::class,'question_option_id'); }
}
