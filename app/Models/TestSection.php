<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Test $test
 * @property-read Collection<int, Question> $questions
 */
class TestSection extends Model
{
    protected $fillable = ['test_id', 'title', 'description', 'instructions', 'sort_order'];

    /** @return BelongsTo<Test, $this> */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /** @return HasMany<Question, $this> */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }
}
