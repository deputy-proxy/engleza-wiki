<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug'];

    public function resources()
    {
        return $this->belongsToMany(Resource::class);
    }
}
