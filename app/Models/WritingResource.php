<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WritingResource extends Model {
    protected $fillable = ['resource_id','prompt','instructions','minimum_words','maximum_words','recommended_time_minutes','rubric','example_answer'];
    public function resource(){ return $this->belongsTo(Resource::class); }
    public function submissions(){ return $this->hasMany(WritingSubmission::class); }
}
