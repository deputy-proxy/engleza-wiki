<?php
namespace Database\Factories;

use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TestFactory extends Factory {
    protected $model = Test::class;
    public function definition(): array {
        $title = 'Test '.$this->faker->unique()->numberBetween(1,100000);
        return [
            'title'=>$title,'slug'=>Str::slug($title),
            'description'=>$this->faker->sentence(),
            'access_type'=>'free','status'=>'published','is_public'=>true,
            'duration_minutes'=>10,'passing_score'=>60,'published_at'=>now(),
        ];
    }
}
