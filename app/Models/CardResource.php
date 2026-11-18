<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardResource extends Model
{
    use HasTranslations;

    protected $fillable = ['resource_id', 'instructions', 'sort_order'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    /** @return HasMany<ResourceCard, $this> */
    public function cards()
    {
        return $this->hasMany(ResourceCard::class)->orderBy('sort_order');
    }
}
