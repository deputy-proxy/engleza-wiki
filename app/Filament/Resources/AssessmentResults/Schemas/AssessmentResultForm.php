<?php
namespace App\Filament\Resources\AssessmentResults\Schemas;
use Filament\Forms\Components\{Select, TextInput, Textarea};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssessmentResultForm {
    public static function configure(Schema $schema): Schema {
        return $schema->components([
            Section::make('Attempt')->columns(2)->schema([
                Select::make('test_attempt_id')->label('Test attempt')->relationship('attempt','id')->searchable()->required(),
                TextInput::make('level')->readOnly(),
                TextInput::make('overall_score')->numeric()->readOnly(),
                TextInput::make('overall_percentage')->numeric()->suffix('%')->readOnly(),
            ]),
            Section::make('Skill breakdown')->columns(3)->schema([
                TextInput::make('grammar_score')->numeric()->suffix('%')->readOnly(),
                TextInput::make('vocabulary_score')->numeric()->suffix('%')->readOnly(),
                TextInput::make('reading_score')->numeric()->suffix('%')->readOnly(),
                TextInput::make('writing_score')->numeric()->suffix('%')->readOnly(),
                TextInput::make('listening_score')->numeric()->suffix('%')->readOnly(),
                TextInput::make('speaking_score')->numeric()->suffix('%')->readOnly(),
            ]),
            Section::make('Recommendation')->schema([
                Textarea::make('recommendation')->rows(6)->columnSpanFull()->readOnly(),
            ]),
        ]);
    }
}
