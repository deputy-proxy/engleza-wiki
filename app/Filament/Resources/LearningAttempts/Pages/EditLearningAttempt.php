<?php

namespace App\Filament\Resources\LearningAttempts\Pages;

use App\Filament\Resources\LearningAttempts\LearningAttemptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningAttempt extends EditRecord
{
    protected static string $resource = LearningAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
