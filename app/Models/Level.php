<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Level extends Model {
    protected $fillable = ['name','slug','description','sort_order'];
    public function resources(){ return $this->hasMany(Resource::class); }
    public function tests(){ return $this->hasMany(Test::class); }
}
