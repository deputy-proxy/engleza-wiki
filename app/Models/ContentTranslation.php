<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $locale
 * @property string $field
 * @property string $value
 * @property string $status
 */
class ContentTranslation extends Model
{
    protected $fillable = ['translatable_type', 'translatable_id', 'locale', 'field', 'value', 'status'];

    /** @return MorphTo<Model, $this> */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
