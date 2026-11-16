<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title.'-'.$this->faker->unique()->numberBetween(1, 100000)),
            'excerpt' => $this->faker->sentence(15),
            'content' => $this->faker->paragraphs(4, true),
            'type' => 'article',
            'status' => 'published',
            'level_id' => Level::inRandomOrder()->first()?->id,
            'author_id' => User::inRandomOrder()->first()?->id,
            'featured' => false,
            'published_at' => now()->subDays(rand(1, 30)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }
}
