<?php

namespace App\Filament\Resources\Resources\Support;

use App\Models\{Resource, CardResource, ListeningResource, ReadingResource, WritingResource};

class NestedTypeContent
{
    private const KEYS = ['cardResource','listeningResource','readingResource','writingResource'];

    public static function extract(array $data): array
    {
        $nested = [];
        foreach (self::KEYS as $k) {
            if (array_key_exists($k, $data)) { $nested[$k] = $data[$k]; unset($data[$k]); }
        }
        return [$data, $nested];
    }

    public static function hydrate(Resource $record, array $data): array
    {
        foreach (self::KEYS as $k) {
            $model = $record->{$k};
            if ($model) $data[$k] = $model->only($model->getFillable());
        }
        return $data;
    }

    public static function persist(Resource $record, array $nested): void
    {
        if ($record->type === 'card' && isset($nested['cardResource'])) {
            CardResource::updateOrCreate(['resource_id'=>$record->id], $nested['cardResource'] ?? []);
        }
        if ($record->type === 'listening' && isset($nested['listeningResource'])) {
            ListeningResource::updateOrCreate(['resource_id'=>$record->id], $nested['listeningResource'] ?? []);
        }
        if ($record->type === 'reading' && isset($nested['readingResource'])) {
            ReadingResource::updateOrCreate(['resource_id'=>$record->id], $nested['readingResource'] ?? []);
        }
        if ($record->type === 'writing' && isset($nested['writingResource'])) {
            WritingResource::updateOrCreate(['resource_id'=>$record->id], $nested['writingResource'] ?? []);
        }
    }
}
