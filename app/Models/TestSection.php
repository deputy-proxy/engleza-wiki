<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TestSection extends Model {
    protected $fillable = ['test_id','title','description','instructions','sort_order'];
    public function test(){ return $this->belongsTo(Test::class); }
    public function questions(){ return $this->hasMany(Question::class)->orderBy('sort_order'); }
}
