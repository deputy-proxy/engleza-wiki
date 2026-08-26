<?php

namespace App\Filament\Resources\LearningAttempts\Pages;

use App\Filament\Resources\LearningAttempts\LearningAttemptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningAttempts extends ListRecords
{
    protected static string $resource = LearningAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
