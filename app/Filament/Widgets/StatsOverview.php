<?php
namespace App\Filament\Widgets;

use App\Models\{Resource, Test, User, TestAttempt};
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $completed = TestAttempt::where('status','completed');
        $avg = (clone $completed)->avg('percentage');
        return [
            Stat::make('Published Resources', Resource::where('status','published')->count())->color('success'),
            Stat::make('Published Tests', Test::where('status','published')->count())->color('primary'),
            Stat::make('Registered Users', User::count())->color('info'),
            Stat::make('Test Attempts', TestAttempt::count())->description('Completed: '.$completed->count()),
            Stat::make('Average Test Score', $avg ? round($avg,1).'%' : '—')->color('warning'),
        ];
    }
}
