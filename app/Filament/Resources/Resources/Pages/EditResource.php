<?php

namespace App\Filament\Resources\Resources\Pages;

use App\Filament\Resources\Resources\ResourceResource;
use App\Filament\Resources\Resources\Support\NestedTypeContent;
use App\Models\Resource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResource extends EditRecord
{
    protected static string $resource = ResourceResource::class;

    protected array $nested = [];

    /** @var array<string, array<int, int|string>> */
    protected array $editorialTaxonomyBefore = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        if (! $this->record instanceof Resource) {
            return;
        }

        foreach (['skills', 'categories', 'topics', 'audiences', 'tags'] as $relation) {
            $this->editorialTaxonomyBefore[$relation] = $this->record->{$relation}()->pluck('id')->sort()->values()->all();
        }
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
        if (! $this->record instanceof Resource) {
            return;
        }

        NestedTypeContent::persist($this->record, $this->nested);

        foreach ($this->editorialTaxonomyBefore as $relation => $before) {
            $after = $this->record->{$relation}()->pluck('id')->sort()->values()->all();
            if ($before !== $after) {
                $this->record->invalidateEditorialReview();
                break;
            }
        }
    }
}
