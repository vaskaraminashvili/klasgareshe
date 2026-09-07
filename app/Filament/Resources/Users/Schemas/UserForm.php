<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AgeGroup;
use App\Enums\DailyGoal;
use App\Enums\Gender;
use App\Enums\OnboardingStep;
use App\Enums\ReminderTime;
use App\Enums\SchoolGrade;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('surname')
                    ->required(),
                TextInput::make('nickname')
                    ->required(),
                TextInput::make('avatar'),
                TextInput::make('age')
                    ->numeric(),
                Select::make('gender')
                    ->options(Gender::class),
                Select::make('age_group')
                    ->options(AgeGroup::class),
                Select::make('grade')
                    ->options(SchoolGrade::class),
                TextInput::make('favourite_subjects'),
                Select::make('daily_goal')
                    ->options(DailyGoal::class),
                Select::make('onboarding_step')
                    ->options(OnboardingStep::class),
                DateTimePicker::make('onboarding_completed_at'),
                TextInput::make('notification_preferences'),
                Select::make('reminder_time')
                    ->options(ReminderTime::class),
                Toggle::make('show_on_leaderboard')
                    ->required(),
                Toggle::make('allow_friend_requests')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
            ]);
    }
}
