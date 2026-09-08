<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Enums\AgeGroup;
use App\Enums\FavouriteSubject;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code'),
                Select::make('format')
                    ->options(QuestionFormat::class)
                    ->required(),
                Select::make('source')
                    ->options(GameType::class),
                Select::make('subject')
                    ->options(FavouriteSubject::class)
                    ->required(),
                Select::make('age_group')
                    ->options(AgeGroup::class),
                TextInput::make('grade')
                    ->numeric(),
                TextInput::make('locale')
                    ->required()
                    ->default('ka'),
                Textarea::make('prompt')
                    ->columnSpanFull(),
                Textarea::make('hint')
                    ->columnSpanFull(),
                TextInput::make('media'),
                TextInput::make('payload')
                    ->required(),
                TextInput::make('answer')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
