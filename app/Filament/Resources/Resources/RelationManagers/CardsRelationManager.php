<?php

namespace App\Filament\Resources\Resources\RelationManagers;

use App\Models\CardResource;
use App\Models\ResourceCard;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';

    protected static ?string $recordTitleAttribute = 'front';

    protected static ?string $title = 'Cards';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === 'card';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('front')->required()->rows(3)->columnSpanFull(),
            Textarea::make('back')->required()->rows(3)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('front')->limit(60)->wrap()->searchable(),
                TextColumn::make('back')->limit(60)->wrap()->searchable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->using(function (array $data): ResourceCard {
                    $cardResource = CardResource::firstOrCreate(['resource_id' => $this->getOwnerRecord()->id]);

                    return ResourceCard::create([
                        'card_resource_id' => $cardResource->id,
                        'front' => $data['front'],
                        'back' => $data['back'],
                        'sort_order' => $data['sort_order'] ?? 0,
                    ]);
                }),
                Action::make('bulkAdd')
                    ->label('Bulk add')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->modalHeading('Bulk add flashcards')
                    ->modalDescription('Paste one card per line. Separate front and back with a tab, a pipe (|), or a semicolon (;). Empty lines are ignored.')
                    ->modalSubmitActionLabel('Create cards')
                    ->schema([
                        Select::make('separator')
                            ->label('Column separator')
                            ->native(false)
                            ->options([
                                'auto' => 'Auto-detect (tab, |, or ;)',
                                'tab' => 'Tab (spreadsheet paste)',
                                'pipe' => 'Pipe |',
                                'semicolon' => 'Semicolon ;',
                                'comma' => 'Comma ,',
                            ])->default('auto')->required(),
                        Textarea::make('lines')
                            ->label('One card per line — front then back')
                            ->rows(12)
                            ->required()
                            ->placeholder("A place where you borrow books.\tLIBRARY\nGO\twent → gone\nWhat is the past of eat?\tATE")
                            ->helperText('Example with pipes: `A place where you borrow books. | LIBRARY`')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        $cardResource = CardResource::firstOrCreate(['resource_id' => $this->getOwnerRecord()->id]);
                        $sep = $data['separator'] ?? 'auto';
                        $sepChar = match ($sep) {
                            'tab' => "\t", 'pipe' => '|', 'semicolon' => ';', 'comma' => ',', default => null,
                        };
                        $created = 0;
                        $skipped = 0;
                        $start = $cardResource->cards()->max('sort_order') ?? -1;
                        foreach (preg_split('/\r?\n/', (string) $data['lines']) as $rawLine) {
                            $line = trim($rawLine);
                            if ($line === '') {
                                continue;
                            }
                            // auto-detect separator per line
                            $use = $sepChar;
                            if ($use === null) {
                                foreach (["\t", '|', ';', ','] as $c) {
                                    if (str_contains($line, $c)) {
                                        $use = $c;
                                        break;
                                    }
                                }
                            }
                            if ($use === null) {
                                $skipped++;

                                continue;
                            }
                            $parts = array_map('trim', explode($use, $line, 2));
                            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                                $skipped++;

                                continue;
                            }
                            ResourceCard::create([
                                'card_resource_id' => $cardResource->id,
                                'front' => $parts[0],
                                'back' => $parts[1],
                                'sort_order' => ++$start,
                            ]);
                            $created++;
                        }
                        Notification::make()
                            ->title("Created {$created} card(s)".($skipped ? ", skipped {$skipped}" : ''))
                            ->success()->send();
                    }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
