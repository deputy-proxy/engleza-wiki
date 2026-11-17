<?php

namespace App\Filament\Resources\LearningUnits\Pages;

use App\Filament\Resources\LearningUnits\LearningUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningUnit extends EditRecord
{
    protected static string $resource = LearningUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
