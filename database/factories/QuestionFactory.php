<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\TestSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'test_section_id' => TestSection::factory(),
            'type' => 'single_choice',
            'question' => $this->faker->sentence().' ___?',
            'points' => 1, 'difficulty' => 'medium', 'sort_order' => 0,
        ];
    }
}
