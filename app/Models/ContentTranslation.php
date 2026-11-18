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
 * @property string|null $source_hash
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 */
class ContentTranslation extends Model
{
    protected $fillable = ['translatable_type', 'translatable_id', 'locale', 'field', 'value', 'source_hash', 'status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    /** @return MorphTo<Model, $this> */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
