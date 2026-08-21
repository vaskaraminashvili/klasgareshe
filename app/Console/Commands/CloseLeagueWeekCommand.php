<?php

namespace App\Console\Commands;

use App\Services\LeagueSeasonService;
use Illuminate\Console\Command;

class CloseLeagueWeekCommand extends Command
{
    protected $signature = 'league:close-week';

    protected $description = 'Close due league weeks and open the current week';

    public function handle(LeagueSeasonService $leagues): int
    {
        $closed = $leagues->closeDueWeeks();
        $leagues->ensureCurrentWeek();

        $this->info("Closed {$closed} league week(s).");

        return self::SUCCESS;
    }
}
