<?php

namespace App\Filament\Resources\Resources\Pages;

use App\Filament\Resources\Resources\ResourceResource;
use App\Filament\Resources\Resources\Support\NestedTypeContent;
use Filament\Resources\Pages\CreateRecord;

class CreateResource extends CreateRecord
{
    protected static string $resource = ResourceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->nested] = NestedTypeContent::extract($data);

        return $data;
    }

    protected array $nested = [];

    protected function afterCreate(): void
    {
        NestedTypeContent::persist($this->record, $this->nested);
    }
}
