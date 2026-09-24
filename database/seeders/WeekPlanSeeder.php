<?php

namespace Database\Seeders;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Enums\SchoolGrade;
use App\Enums\SchoolSubject;
use App\Models\Game;
use App\Models\Question;
use App\Models\WeekPlanItem;
use Illuminate\Database\Seeder;

class WeekPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([GameType::MultipleChoice, GameType::TapCorrect, GameType::Counting] as $type) {
            $this->gameFor($type);
        }

        foreach (SchoolGrade::cases() as $grade) {
            $weeks = $grade === SchoolGrade::First ? [1, 2, 3] : [1, 2];

            foreach ($weeks as $weekNumber) {
                foreach (SchoolSubject::ordered() as $subject) {
                    for ($weekday = 1; $weekday <= 7; $weekday++) {
                        $this->seedPack($grade, $subject, $weekday, $weekNumber);
                    }
                }
            }
        }
    }

    private function seedPack(
        SchoolGrade $grade,
        SchoolSubject $subject,
        int $weekday,
        int $weekNumber = 1,
    ): void {
        $type = WeekPlanQuestionBank::gameType($grade, $subject, $weekday, $weekNumber);
        $game = $this->gameFor($type);
        $pack = WeekPlanQuestionBank::pack($grade, $subject, $weekday, $weekNumber);

        $item = WeekPlanItem::query()->updateOrCreate(
            [
                'grade' => $grade,
                'week_number' => $weekNumber,
                'weekday' => $weekday,
                'subject' => $subject,
            ],
            [
                'level' => (($weekNumber - 1) * 7) + $weekday,
                'title' => $pack['title'],
                'game_slug' => $type,
                'questions_per_round' => 5,
            ],
        );

        $questionIds = [];

        foreach ($pack['questions'] as $index => $row) {
            $code = sprintf(
                'g%d-w%d-d%d-%s-%02d',
                $grade->value,
                $weekNumber,
                $weekday,
                $subject->value,
                $index + 1,
            );

            $choices = $this->choices($row['correct'], $row['wrongs']);
            $correctKey = 'A';

            foreach ($choices as $choice) {
                if ($choice['label'] === $row['correct']) {
                    $correctKey = $choice['key'];
                    break;
                }
            }

            $question = Question::query()->updateOrCreate(
                ['code' => $code],
                [
                    'format' => $type->format(),
                    'source' => $type,
                    'subject' => $subject->favourite(),
                    'age_group' => null,
                    'grade' => $grade->value,
                    'locale' => 'ka',
                    'prompt' => $row['prompt'],
                    'hint' => $type === GameType::TapCorrect
                        ? 'რიცხვები ციფრებით იწერება, მაგალითად 0–9.'
                        : null,
                    'media' => [
                        'emoji' => $row['emoji'],
                        'tile' => $subject->tile(),
                    ],
                    'payload' => $type === GameType::Counting
                        ? $this->countPayload($row, $choices)
                        : ['choices' => $choices],
                    'answer' => $type === GameType::Counting
                        ? ['key' => $correctKey, 'value' => (int) $row['correct']]
                        : ['key' => $correctKey],
                    'is_active' => true,
                ],
            );

            $questionIds[$question->id] = ['sort_order' => $index];
        }

        $item->questions()->sync($questionIds);
        $game->questions()->syncWithoutDetaching(array_keys($questionIds));
    }

    private function gameFor(GameType $type): Game
    {
        $defaults = $type->playDefaults();

        return Game::query()->updateOrCreate(
            [
                'slug' => $type,
                'user_id' => null,
            ],
            [
                'format' => $type->format(),
                'lives' => 3,
                'questions_per_round' => 5,
                'xp_per_correct' => $defaults['xp_per_correct'],
                'is_active' => true,
                'visibility' => GameVisibility::Public,
            ],
        );
    }

    /**
     * @param  array{prompt: string, correct: string, wrongs: list<string>, emoji: string}  $row
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     * @return array{item_emoji: string, count: int, choices: list<array{key: string, label: string, emoji: string, value: int}>}
     */
    private function countPayload(array $row, array $choices): array
    {
        $payloadChoices = [];

        foreach ($choices as $choice) {
            $payloadChoices[] = [
                'key' => $choice['key'],
                'label' => $choice['label'],
                'emoji' => $choice['emoji'],
                'value' => (int) $choice['label'],
            ];
        }

        return [
            'item_emoji' => $row['emoji'],
            'count' => (int) $row['correct'],
            'choices' => $payloadChoices,
        ];
    }

    /**
     * @param  list<string>  $wrongs
     * @return list<array{key: string, label: string, emoji: string}>
     */
    private function choices(string $correct, array $wrongs): array
    {
        $labels = array_values(array_unique(array_merge([$correct], $wrongs)));
        $labels = array_slice($labels, 0, 4);

        while (count($labels) < 4) {
            $labels[] = $correct.'?';
        }

        shuffle($labels);

        $keys = ['A', 'B', 'C', 'D'];
        $choices = [];

        foreach ($labels as $index => $label) {
            $choices[] = [
                'key' => $keys[$index],
                'label' => $label,
                'emoji' => '',
            ];
        }

        return $choices;
    }
}
