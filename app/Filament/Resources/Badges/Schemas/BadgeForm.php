<?php

namespace App\Filament\Resources\Badges\Schemas;

use App\Enums\BadgeCategory;
use App\Enums\BadgeRarity;
use App\Enums\BadgeRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BadgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required(),
                Select::make('rarity')
                    ->options(BadgeRarity::class)
                    ->required(),
                Select::make('category')
                    ->options(BadgeCategory::class)
                    ->required(),
                TextInput::make('emoji')
                    ->required(),
                TextInput::make('medal'),
                TextInput::make('xp_bonus')
                    ->required()
                    ->numeric()
                    ->default(100),
                Select::make('rule')
                    ->options(BadgeRule::class)
                    ->required(),
                TextInput::make('rule_params'),
                Toggle::make('is_secret')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
