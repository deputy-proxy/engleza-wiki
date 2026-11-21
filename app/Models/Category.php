<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'description', 'parent_id', 'type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'bool'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** @return BelongsToMany<\App\Models\Resource, $this> */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
