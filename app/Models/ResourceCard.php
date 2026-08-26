<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResourceCard extends Model {
    protected $fillable = ['card_resource_id','front','back','sort_order'];
    public function cardResource(){ return $this->belongsTo(CardResource::class); }
}
