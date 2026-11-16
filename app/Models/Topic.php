<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'sort_order'];

    public function parent()
    {
        return $this->belongsTo(Topic::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Topic::class, 'parent_id');
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class);
    }
}
