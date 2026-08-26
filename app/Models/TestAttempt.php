<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TestAttempt extends Model {
    protected $fillable = ['test_id','user_id','session_id','started_at','completed_at','status','score','percentage','level_result','time_spent_seconds','ip_address','user_agent'];
    protected $casts = ['started_at'=>'datetime','completed_at'=>'datetime'];
    public function test(){ return $this->belongsTo(Test::class); }
    public function user(){ return $this->belongsTo(User::class); }
    public function answers(){ return $this->hasMany(TestAnswer::class); }
    public function result(){ return $this->hasOne(AssessmentResult::class); }
}
