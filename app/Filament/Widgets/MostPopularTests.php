<?php

namespace App\Filament\Widgets;

use App\Models\Resource;
use App\Models\Test;
use Filament\Widgets\Widget;

class MostPopularTests extends Widget
{
    protected string $view = 'filament.widgets.most-popular-tests';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $popularTests = Test::withCount('attempts')->orderByDesc('attempts_count')->limit(5)->get();
        $popularResources = Resource::published()->orderByDesc('published_at')->limit(5)->get();

        return compact('popularTests', 'popularResources');
    }
}
