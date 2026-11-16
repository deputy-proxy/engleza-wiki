<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }
}
