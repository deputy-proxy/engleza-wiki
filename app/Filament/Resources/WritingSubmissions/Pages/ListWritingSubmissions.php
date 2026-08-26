<?php

namespace App\Filament\Resources\WritingSubmissions\Pages;

use App\Filament\Resources\WritingSubmissions\WritingSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWritingSubmissions extends ListRecords
{
    protected static string $resource = WritingSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
