<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuestionOption extends Model {
    protected $fillable = ['question_id','label','value','is_correct','sort_order'];
    protected $casts = ['is_correct'=>'bool'];
    public function question(){ return $this->belongsTo(Question::class); }
}
