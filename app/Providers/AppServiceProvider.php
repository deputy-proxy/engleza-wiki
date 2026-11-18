<?php

namespace App\Providers;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\LessonActivity;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Media;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ReadingResource;
use App\Models\ResourceCard;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\WritingResource;
use App\Observers\EditorialReviewObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Audience::class,
            CardResource::class,
            Category::class,
            ContentTranslation::class,
            LessonActivity::class,
            ListeningResource::class,
            Level::class,
            Media::class,
            Question::class,
            QuestionOption::class,
            ReadingResource::class,
            ResourceCard::class,
            Skill::class,
            Tag::class,
            Topic::class,
            TestSection::class,
            WritingResource::class,
        ] as $model) {
            $model::observe(EditorialReviewObserver::class);
        }
    }
}
