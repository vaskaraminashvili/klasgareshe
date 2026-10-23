<?php

namespace App\Services;

use App\Enums\GameType;
use App\Enums\SchoolSubject;
use App\Repositories\BadgeRepository;

class AboutService
{
    public function __construct(
        private BadgeRepository $badges,
        private AppLocaleService $locales,
    ) {}

    public function version(): string
    {
        return (string) config('kidzio.version');
    }

    public function badgeCount(): int
    {
        return $this->badges->countCatalog();
    }

    public function subjectCount(): int
    {
        return count(SchoolSubject::cases());
    }

    public function localeCount(): int
    {
        return count($this->locales->available());
    }

    public function liveGameCount(): int
    {
        $live = 0;

        foreach (GameType::cases() as $game) {
            if ($game->playerRoute() === $game->routeName()) {
                $live++;
            }
        }

        return $live;
    }
}
