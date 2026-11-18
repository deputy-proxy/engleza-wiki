<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->label('Email address')->email()->required()->unique(ignoreRecord: true),
            Select::make('role')->required()->native(false)->options([
                'admin' => 'Admin', 'editor' => 'Editor', 'teacher' => 'Teacher', 'learner' => 'Learner',
            ])->default('learner')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->dehydrated(fn (): bool => auth()->user()?->isAdmin() ?? false),
            DateTimePicker::make('email_verified_at')->label('Verified at'),
            TextInput::make('password')->password()->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('Leave blank to keep the current password.'),
        ])->columns(2);
    }
}
