<?php

namespace App\Filament\Widgets;

use App\Models\TestAttempt;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AttemptsChart extends ChartWidget
{
    protected ?string $heading = 'Test attempts — last 12 weeks';
    protected int|string|array $columnSpan = 'full';
    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $weeks = collect(range(11, 0))->map(fn ($n) => Carbon::now()->startOfWeek()->subWeeks($n));

        $labels = $weeks->map(fn ($w) => $w->format('M d'))->all();

        $counts = $weeks->map(function ($start) {
            $end = (clone $start)->endOfWeek();
            return TestAttempt::whereBetween('created_at', [$start, $end])->count();
        })->all();

        $completed = $weeks->map(function ($start) {
            $end = (clone $start)->endOfWeek();
            return TestAttempt::whereBetween('completed_at', [$start, $end])->where('status', 'completed')->count();
        })->all();

        return [
            'datasets' => [
                [
                    'label' => 'Attempts started',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(44,124,255,0.6)',
                    'borderColor' => 'rgba(44,124,255,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Attempts completed',
                    'data' => $completed,
                    'backgroundColor' => 'rgba(16,185,129,0.6)',
                    'borderColor' => 'rgba(16,185,129,1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
