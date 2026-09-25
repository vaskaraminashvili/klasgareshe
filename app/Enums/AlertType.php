<?php

namespace App\Enums;

enum AlertType: string
{
    case BadgeUnlocked = 'badge_unlocked';
    case LeaguePromoted = 'league_promoted';
    case LeagueRelegated = 'league_relegated';
    case StreakAtRisk = 'streak_at_risk';
    case MissionReady = 'mission_ready';
    case NewWeek = 'new_week';
    case FriendAccepted = 'friend_accepted';

    public function preferenceKey(): string
    {
        return match ($this) {
            self::BadgeUnlocked, self::LeaguePromoted, self::LeagueRelegated => 'rewards',
            self::StreakAtRisk => 'streak',
            self::MissionReady => 'daily_mission',
            self::NewWeek => 'new_lessons',
            self::FriendAccepted => 'friend_activity',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BadgeUnlocked => 'ph-medal',
            self::LeaguePromoted, self::LeagueRelegated => 'ph-trophy',
            self::StreakAtRisk => 'ph-fire',
            self::MissionReady => 'ph-target',
            self::NewWeek => 'ph-book-open',
            self::FriendAccepted => 'ph-user-plus',
        };
    }

    public function tile(): string
    {
        return match ($this) {
            self::BadgeUnlocked => 'tile-mint',
            self::LeaguePromoted => 'tile-pink',
            self::LeagueRelegated => 'tile-coral',
            self::StreakAtRisk => 'tile-sun',
            self::MissionReady => 'tile-sky',
            self::NewWeek => 'tile-violet',
            self::FriendAccepted => 'tile-mint',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::BadgeUnlocked => 'badges',
            self::LeaguePromoted, self::LeagueRelegated => 'league',
            self::StreakAtRisk => 'streak',
            self::MissionReady => 'daily-mission',
            self::NewWeek => 'learn-categories',
            self::FriendAccepted => 'ranking-friends',
        };
    }
}
