<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CardResource extends Model {
    protected $fillable = ['resource_id','instructions','sort_order'];
    public function resource(){ return $this->belongsTo(Resource::class); }
    public function cards(){ return $this->hasMany(ResourceCard::class)->orderBy('sort_order'); }
}
