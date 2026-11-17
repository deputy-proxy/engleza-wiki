<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Test $test
 * @property-read AssessmentResult|null $result
 * @property-read Collection<int, TestAnswer> $answers
 */
class TestAttempt extends Model
{
    protected $fillable = ['test_id', 'user_id', 'class_group_id', 'session_id', 'started_at', 'completed_at', 'status', 'score', 'percentage', 'level_result', 'time_spent_seconds', 'ip_address', 'user_agent', 'question_order', 'total_points', 'passed'];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime', 'question_order' => 'array', 'total_points' => 'integer', 'passed' => 'boolean'];

    /** @return BelongsTo<Test, $this> */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** @return HasMany<TestAnswer, $this> */
    public function answers()
    {
        return $this->hasMany(TestAnswer::class);
    }

    /** @return HasOne<AssessmentResult, $this> */
    public function result()
    {
        return $this->hasOne(AssessmentResult::class);
    }
}
