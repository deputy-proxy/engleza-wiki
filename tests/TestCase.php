<?php

namespace Tests;

use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function approveSeededContent(): void
    {
        $reviewer = User::query()->where('role', 'admin')->orderBy('id')->first();
        if ($reviewer === null) {
            return;
        }

        $review = [
            'editorial_status' => 'approved',
            'editorial_reviewed_by' => $reviewer->id,
            'editorial_reviewed_at' => now(),
        ];

        foreach ([Resource::class, Assessment::class, LearningLesson::class, ContentTranslation::class] as $model) {
            $model::query()->where('editorial_status', 'pending')->update($review);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
