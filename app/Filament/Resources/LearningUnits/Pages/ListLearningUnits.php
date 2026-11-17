<?php

namespace App\Filament\Resources\LearningUnits\Pages;

use App\Filament\Resources\LearningUnits\LearningUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningUnits extends ListRecords
{
    protected static string $resource = LearningUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
