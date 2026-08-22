<?php

namespace App\Enums;

enum BadgeRule: string
{
    case PacksCompleted = 'packs_completed';
    case SubjectPacksCompleted = 'subject_packs_completed';
    case StreakDays = 'streak_days';
    case PerfectPacks = 'perfect_packs';
    case ConsecutivePerfectPacks = 'consecutive_perfect_packs';
    case SubjectsStarted = 'subjects_started';
    case SubjectCorrectAnswers = 'subject_correct_answers';
    case MissionToday = 'mission_today';
    case PlayAfterHour = 'play_after_hour';
    case LeagueTopFinish = 'league_top_finish';
    case WeekPacksCompleted = 'week_packs_completed';
    case SubjectAllPerfect = 'subject_all_perfect';
    case Locked = 'locked';
}
