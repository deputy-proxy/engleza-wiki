<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model {
    use HasFactory;
    protected $fillable = ['test_section_id','type','question','explanation','points','difficulty','sort_order','metadata'];
    protected $casts = ['metadata'=>'array'];
    public function section(){ return $this->belongsTo(TestSection::class,'test_section_id'); }
    public function options(){ return $this->hasMany(QuestionOption::class)->orderBy('sort_order'); }
    public function topics(){ return $this->belongsToMany(Topic::class); }
}
