<?php

namespace Database\Seeders;

use App\Enums\BadgeCategory;
use App\Enums\BadgeRarity;
use App\Enums\BadgeRule;
use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->catalog() as $index => $badge) {
            Badge::query()->updateOrCreate(
                ['slug' => $badge['slug']],
                [
                    'rarity' => $badge['rarity'],
                    'category' => $badge['category'],
                    'emoji' => $badge['emoji'],
                    'medal' => $badge['medal'],
                    'xp_bonus' => 100,
                    'rule' => $badge['rule'],
                    'rule_params' => $badge['params'],
                    'is_secret' => $badge['secret'],
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    /**
     * @return list<array{
     *     slug: string,
     *     rarity: BadgeRarity,
     *     category: BadgeCategory,
     *     emoji: string,
     *     medal: string|null,
     *     rule: BadgeRule,
     *     params: array<string, int|string>,
     *     secret: bool
     * }>
     */
    private function catalog(): array
    {
        return [
            [
                'slug' => 'first-win',
                'rarity' => BadgeRarity::Common,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🏆',
                'medal' => null,
                'rule' => BadgeRule::PacksCompleted,
                'params' => ['count' => 1],
                'secret' => false,
            ],
            [
                'slug' => 'star-mini',
                'rarity' => BadgeRarity::Common,
                'category' => BadgeCategory::Milestone,
                'emoji' => '⭐',
                'medal' => null,
                'rule' => BadgeRule::PacksCompleted,
                'params' => ['count' => 3],
                'secret' => false,
            ],
            [
                'slug' => 'bookworm',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Alphabet,
                'emoji' => '📚',
                'medal' => 'silver',
                'rule' => BadgeRule::SubjectPacksCompleted,
                'params' => ['subject' => 'georgian', 'count' => 3],
                'secret' => false,
            ],
            [
                'slug' => 'letter-lord',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Alphabet,
                'emoji' => '🔤',
                'medal' => 'silver',
                'rule' => BadgeRule::SubjectPacksCompleted,
                'params' => ['subject' => 'georgian', 'count' => 7],
                'secret' => false,
            ],
            [
                'slug' => 'on-fire',
                'rarity' => BadgeRarity::Common,
                'category' => BadgeCategory::Streak,
                'emoji' => '🔥',
                'medal' => 'bronze',
                'rule' => BadgeRule::StreakDays,
                'params' => ['count' => 7],
                'secret' => false,
            ],
            [
                'slug' => 'target-pro',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Math,
                'emoji' => '🎯',
                'medal' => null,
                'rule' => BadgeRule::SubjectPacksCompleted,
                'params' => ['subject' => 'math', 'count' => 5],
                'secret' => false,
            ],
            [
                'slug' => 'math-pro',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Math,
                'emoji' => '➗',
                'medal' => null,
                'rule' => BadgeRule::SubjectPacksCompleted,
                'params' => ['subject' => 'math', 'count' => 7],
                'secret' => false,
            ],
            [
                'slug' => 'puzzler',
                'rarity' => BadgeRarity::Epic,
                'category' => BadgeCategory::Math,
                'emoji' => '🧩',
                'medal' => 'silver',
                'rule' => BadgeRule::PerfectPacks,
                'params' => ['count' => 1],
                'secret' => false,
            ],
            [
                'slug' => 'animal-pal',
                'rarity' => BadgeRarity::Common,
                'category' => BadgeCategory::Animals,
                'emoji' => '🐾',
                'medal' => 'bronze',
                'rule' => BadgeRule::SubjectPacksCompleted,
                'params' => ['subject' => 'history', 'count' => 7],
                'secret' => false,
            ],
            [
                'slug' => 'quiz-master',
                'rarity' => BadgeRarity::Epic,
                'category' => BadgeCategory::Milestone,
                'emoji' => '❓',
                'medal' => null,
                'rule' => BadgeRule::PacksCompleted,
                'params' => ['count' => 50],
                'secret' => false,
            ],
            [
                'slug' => '30-day-flame',
                'rarity' => BadgeRarity::Legend,
                'category' => BadgeCategory::Streak,
                'emoji' => '🔥',
                'medal' => null,
                'rule' => BadgeRule::StreakDays,
                'params' => ['count' => 30],
                'secret' => false,
            ],
            [
                'slug' => 'word-wizard',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Alphabet,
                'emoji' => '📚',
                'medal' => null,
                'rule' => BadgeRule::SubjectCorrectAnswers,
                'params' => ['subject' => 'georgian', 'count' => 100],
                'secret' => false,
            ],
            [
                'slug' => 'explorer',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🧭',
                'medal' => null,
                'rule' => BadgeRule::SubjectsStarted,
                'params' => ['count' => 3],
                'secret' => false,
            ],
            [
                'slug' => 'perfect-10',
                'rarity' => BadgeRarity::Epic,
                'category' => BadgeCategory::Milestone,
                'emoji' => '💯',
                'medal' => null,
                'rule' => BadgeRule::ConsecutivePerfectPacks,
                'params' => ['count' => 10],
                'secret' => false,
            ],
            [
                'slug' => 'night-owl',
                'rarity' => BadgeRarity::Common,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🦉',
                'medal' => null,
                'rule' => BadgeRule::PlayAfterHour,
                'params' => ['hour' => 19, 'count' => 5],
                'secret' => false,
            ],
            [
                'slug' => 'top-3-league',
                'rarity' => BadgeRarity::Epic,
                'category' => BadgeCategory::League,
                'emoji' => '🥇',
                'medal' => null,
                'rule' => BadgeRule::LeagueTopFinish,
                'params' => ['rank' => 3],
                'secret' => false,
            ],
            [
                'slug' => 'speed-runner',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Milestone,
                'emoji' => '⚡',
                'medal' => null,
                'rule' => BadgeRule::Locked,
                'params' => [],
                'secret' => false,
            ],
            [
                'slug' => 'social-star',
                'rarity' => BadgeRarity::Rare,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🌟',
                'medal' => null,
                'rule' => BadgeRule::Locked,
                'params' => [],
                'secret' => false,
            ],
            [
                'slug' => 'secret-week',
                'rarity' => BadgeRarity::Legend,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🌈',
                'medal' => null,
                'rule' => BadgeRule::WeekPacksCompleted,
                'params' => ['count' => 21],
                'secret' => true,
            ],
            [
                'slug' => 'secret-mission',
                'rarity' => BadgeRarity::Legend,
                'category' => BadgeCategory::Milestone,
                'emoji' => '🎁',
                'medal' => null,
                'rule' => BadgeRule::MissionToday,
                'params' => ['count' => 3],
                'secret' => true,
            ],
            [
                'slug' => 'secret-ace',
                'rarity' => BadgeRarity::Legend,
                'category' => BadgeCategory::Milestone,
                'emoji' => '💎',
                'medal' => null,
                'rule' => BadgeRule::SubjectAllPerfect,
                'params' => [],
                'secret' => true,
            ],
        ];
    }
}
