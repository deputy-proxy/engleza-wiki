<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audience extends Model
{
    protected $fillable = ['name', 'slug'];

    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }
}
