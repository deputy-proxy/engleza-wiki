<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resource extends Model {
    use HasFactory;
    protected $fillable = ['title','slug','excerpt','content','type','status','level_id','author_id','featured','published_at','seo_title','seo_description','canonical_url','robots_index','og_title','og_description','og_image'];
    protected $casts = ['featured'=>'bool','robots_index'=>'bool','published_at'=>'datetime'];

    public function author(){ return $this->belongsTo(User::class,'author_id'); }
    public function level(){ return $this->belongsTo(Level::class); }
    public function categories(){ return $this->belongsToMany(Category::class); }
    public function tags(){ return $this->belongsToMany(Tag::class); }
    public function skills(){ return $this->belongsToMany(Skill::class); }
    public function audiences(){ return $this->belongsToMany(Audience::class); }
    public function topics(){ return $this->belongsToMany(Topic::class); }
    public function media(){ return $this->hasMany(Media::class); }
    public function cardResource(){ return $this->hasOne(CardResource::class); }
    public function listeningResource(){ return $this->hasOne(ListeningResource::class); }
    public function readingResource(){ return $this->hasOne(ReadingResource::class); }
    public function writingResource(){ return $this->hasOne(WritingResource::class); }
    public function learningAttempts(){ return $this->hasMany(LearningAttempt::class); }

    public function scopePublished($q){ return $q->where('status','published')->where('published_at','<=',now()); }
    public function getRouteKeyName(): string { return 'slug'; }
}
