<?php

namespace App\Filament\Resources\Resources\Pages;

use App\Filament\Resources\Resources\ResourceResource;
use App\Filament\Resources\Resources\Support\NestedTypeContent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    protected static string $resource = ResourceResource::class;

    protected array $nested = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return NestedTypeContent::hydrate($this->record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        [$data, $this->nested] = NestedTypeContent::extract($data);
        return $data;
    }

    protected function afterSave(): void
    {
        NestedTypeContent::persist($this->record, $this->nested);
    }
}
