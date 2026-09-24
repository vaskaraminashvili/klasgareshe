<?php

namespace App\Services;

class SearchService
{
    public function __construct(private WeekPlanService $week) {}

    /**
     * Static destinations, keyed by their `home.search_to.*` lang entry.
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const DESTINATIONS = [
        'daily_mission' => ['daily-mission', '🎯', 'tile-violet'],
        'library' => ['learn-categories', '📚', 'tile-coral'],
        'badges' => ['badges', '🏅', 'tile-mint'],
        'leaderboard' => ['leaderboard', '🏆', 'tile-sun'],
        'league' => ['league', '🥇', 'tile-sun'],
        'xp' => ['xp-progress', '⭐', 'tile-violet'],
        'monthly_goals' => ['monthly-goals', '🗓️', 'tile-mint'],
        'friends' => ['ranking-friends', '👫', 'tile-sky'],
        'settings' => ['settings', '⚙️', 'tile-mint'],
        'streak' => ['streak', '🔥', 'tile-sun'],
        'counting' => ['game-counting', '🔢', 'tile-sky'],
    ];

    /**
     * Everything the Home search overlay can reach today. Only built screens
     * appear here; the rest of the template catalog arrives with the live
     * index (see docs/tasks/T17-search.md).
     *
     * @param  list<array{id: int|null, subject: string, title: string, subtitle: string, completed: bool, playable: bool, emoji: string, tile: string, inkClass: string, href?: string}>  $planTasks
     * @return list<array{name: string, keys: string, href: string, ico: string, tile: string}>
     */
    public function homeCatalog(array $planTasks, ?int $continueItemId = null): array
    {
        $entries = [];

        foreach ($planTasks as $task) {
            $entries[] = [
                'name' => $task['subject'],
                'keys' => trim($task['subject'].' '.$task['title']),
                'href' => $task['href'] ?? $this->packUrl($task['playable'] ? $task['id'] : null),
                'ico' => $task['emoji'],
                'tile' => $task['tile'],
            ];
        }

        $entries[] = [
            'name' => (string) __('home.search_to.quick_quiz.name'),
            'keys' => (string) __('home.search_to.quick_quiz.keys'),
            'href' => $this->packUrl($continueItemId),
            'ico' => '❓',
            'tile' => 'tile-violet',
        ];

        foreach (self::DESTINATIONS as $key => [$route, $ico, $tile]) {
            $entries[] = [
                'name' => (string) __("home.search_to.{$key}.name"),
                'keys' => (string) __("home.search_to.{$key}.keys"),
                'href' => route($route),
                'ico' => $ico,
                'tile' => $tile,
            ];
        }

        return $entries;
    }

    private function packUrl(?int $itemId): string
    {
        return $this->week->playUrl($itemId, missionIfMissing: true);
    }
}
