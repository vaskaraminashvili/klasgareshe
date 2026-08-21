<?php

namespace App\Enums;

enum LeagueOutcome: string
{
    case Promote = 'promote';
    case Hold = 'hold';
    case Relegate = 'relegate';

    public function label(): string
    {
        return (string) __('ranking.outcome_'.$this->value);
    }
}
