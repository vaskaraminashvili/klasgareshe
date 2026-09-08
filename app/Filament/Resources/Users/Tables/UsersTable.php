<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('surname')
                    ->searchable(),
                TextColumn::make('nickname')
                    ->searchable(),
                TextColumn::make('avatar')
                    ->searchable(),
                TextColumn::make('age')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gender')
                    ->badge()
                    ->searchable(),
                TextColumn::make('age_group')
                    ->badge()
                    ->searchable(),
                TextColumn::make('grade')
                    ->badge()
                    ->sortable(),
                TextColumn::make('daily_goal')
                    ->badge()
                    ->searchable(),
                TextColumn::make('onboarding_step')
                    ->badge()
                    ->searchable(),
                TextColumn::make('onboarding_completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reminder_time')
                    ->badge()
                    ->searchable(),
                IconColumn::make('show_on_leaderboard')
                    ->boolean(),
                IconColumn::make('allow_friend_requests')
                    ->boolean(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
