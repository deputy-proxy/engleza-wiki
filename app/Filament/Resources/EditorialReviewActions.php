<?php

namespace App\Filament\Resources;

use App\Models\ContentTranslation;
use App\Models\Resource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditorialReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approveEditorialReview')
            ->label('Approve editorial review')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve this content')
            ->modalDescription('Approval makes this item eligible for public display. Confirm that the content, answers, CEFR fit and translations have been checked.')
            ->schema([Textarea::make('editorial_notes')->label('Review evidence and rationale')->required()->rows(4)])
            ->action(function (Model $record, array $data): void {
                $reviewer = User::query()->findOrFail(auth()->id());

                if ($record instanceof Resource && $record->type === 'speaking') {
                    $speaking = $record->speakingResource;
                    $issues = $speaking?->publicationIssues() ?? ['A structured speaking task is required before approval.'];
                    if ($issues !== []) {
                        throw ValidationException::withMessages(['editorial_status' => implode(' ', $issues)]);
                    }
                }

                if ($record instanceof ContentTranslation) {
                    $record->approveTranslation($reviewer, $data['editorial_notes']);

                    return;
                }

                if (method_exists($record, 'approveEditorialReview')) {
                    $record->approveEditorialReview($reviewer, $data['editorial_notes']);
                }
            });
    }

    public static function decision(string $decision, string $label, string $color = 'gray'): Action
    {
        return Action::make('editorial'.ucfirst($decision))
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->schema([Textarea::make('editorial_notes')->label('Decision rationale')->required()->rows(4)])
            ->action(function (Model $record, array $data) use ($decision): void {
                $reviewer = User::query()->findOrFail(auth()->id());
                if (method_exists($record, 'setEditorialDecision')) {
                    $record->setEditorialDecision($decision, $reviewer, $data['editorial_notes']);
                }
            });
    }
}
