<?php

namespace App\Filament\Resources\WritingSubmissions\Pages;

use App\Filament\Resources\WritingSubmissions\WritingSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWritingSubmission extends EditRecord
{
    protected static string $resource = WritingSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
