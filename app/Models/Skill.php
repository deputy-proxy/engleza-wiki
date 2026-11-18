<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'description'];

    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }
}
