<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReadingResource extends Model {
    protected $fillable = ['resource_id','title','instructions','content'];
    public function resource(){ return $this->belongsTo(Resource::class); }
    public function questions(){ return $this->hasMany(Question::class)->orderBy('sort_order'); }
}
