<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ReadingResource;
use App\Models\TestSection;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('bulkImport')
                ->label('Bulk import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Bulk import questions')
                ->modalDescription('Paste one question per line as CSV: '.
                    '"question","option A","option B","option C","option D","correct letter (A-D)","explanation"')
                ->modalSubmitActionLabel('Import questions')
                ->schema([
                    Select::make('target')
                        ->native(false)->required()->default('section')
                        ->options(['section' => 'Test section', 'listening' => 'Listening resource', 'reading' => 'Reading resource'])
                        ->live(),
                    Select::make('test_section_id')->label('Test section')
                        ->relationship('section', 'title')->searchable()
                        ->getOptionLabelFromRecordUsing(fn (TestSection $s) => ($s->test->title ? $s->test->title.' — ' : '').$s->title)
                        ->visible(fn ($get) => $get('target') === 'section')
                        ->required(fn ($get) => $get('target') === 'section'),
                    Select::make('listening_resource_id')->label('Listening resource')
                        ->options(fn () => ListeningResource::with('resource')->get()->mapWithKeys(fn ($r) => [$r->id => $r->resource->title ?: '#'.$r->id])->all())
                        ->searchable()
                        ->visible(fn ($get) => $get('target') === 'listening')
                        ->required(fn ($get) => $get('target') === 'listening'),
                    Select::make('reading_resource_id')->label('Reading resource')
                        ->options(fn () => ReadingResource::with('resource')->get()->mapWithKeys(fn ($r) => [$r->id => $r->resource->title ?: '#'.$r->id])->all())
                        ->searchable()
                        ->visible(fn ($get) => $get('target') === 'reading')
                        ->required(fn ($get) => $get('target') === 'reading'),
                    Textarea::make('csv')
                        ->label('CSV rows — one question per line')
                        ->rows(12)
                        ->required()
                        ->placeholder('"She ___ to school every day.","go","goes","going","gone","B","Third person singular takes -s."')
                        ->helperText('Wrap fields in double quotes. Escape a " inside a value by doubling it (""). Fields: question, A, B, C, D, correct letter, explanation (optional).')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $target = $data['target'];
                    $fk = match ($target) {
                        'section' => ['column' => 'test_section_id', 'value' => $data['test_section_id']],
                        'listening' => ['column' => 'listening_resource_id', 'value' => $data['listening_resource_id']],
                        'reading' => ['column' => 'reading_resource_id', 'value' => $data['reading_resource_id']],
                    };
                    $created = 0;
                    $skipped = 0;
                    $errors = [];
                    $order = Question::where($fk['column'], $fk['value'])->max('sort_order') ?? -1;
                    $lines = preg_split('/\r?\n/', (string) $data['csv']);

                    DB::beginTransaction();
                    try {
                        foreach ($lines as $i => $raw) {
                            $line = trim($raw);
                            if ($line === '') {
                                continue;
                            }
                            $row = str_getcsv($line, ',', '"', '\\');
                            if (count($row) < 6) {
                                $skipped++;
                                $errors[] = 'Line '.($i + 1).': needs at least 6 fields';

                                continue;
                            }
                            [$q,$a,$b,$c,$d,$correct] = array_map('trim', array_slice($row, 0, 6));
                            $explanation = trim($row[6] ?? '');
                            $letter = strtoupper($correct);
                            if (! in_array($letter, ['A', 'B', 'C', 'D'], true)) {
                                $skipped++;
                                $errors[] = 'Line '.($i + 1).': invalid correct letter';

                                continue;
                            }
                            $question = Question::create([
                                $fk['column'] => $fk['value'],
                                'type' => 'single_choice',
                                'question' => $q,
                                'explanation' => $explanation ?: null,
                                'points' => 1,
                                'difficulty' => 'medium',
                                'sort_order' => ++$order,
                            ]);
                            foreach ([['A', $a], ['B', $b], ['C', $c], ['D', $d]] as $j => [$lbl,$val]) {
                                QuestionOption::create([
                                    'question_id' => $question->id,
                                    'label' => $lbl,
                                    'value' => $val,
                                    'is_correct' => $lbl === $letter,
                                    'sort_order' => $j,
                                ]);
                            }
                            $created++;
                        }
                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Notification::make()->title('Import failed')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title("Imported {$created} question(s)".($skipped ? ", skipped {$skipped}" : ''))
                        ->body($errors ? implode("\n", array_slice($errors, 0, 5)) : null)
                        ->success()->send();
                }),
        ];
    }
}
