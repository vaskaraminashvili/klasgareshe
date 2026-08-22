<?php

namespace Database\Seeders;

use App\Enums\GameType;
use App\Enums\GameVisibility;
use App\Enums\QuestionFormat;
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
        $game = Game::query()->updateOrCreate(
            [
                'slug' => GameType::MultipleChoice,
                'user_id' => null,
            ],
            [
                'format' => QuestionFormat::Choice,
                'lives' => 3,
                'questions_per_round' => 5,
                'xp_per_correct' => 8,
                'is_active' => true,
                'visibility' => GameVisibility::Public,
            ],
        );

        foreach (SchoolGrade::cases() as $grade) {
            foreach (SchoolSubject::ordered() as $subject) {
                for ($weekday = 1; $weekday <= 7; $weekday++) {
                    $this->seedPack($game, $grade, $subject, $weekday);
                }
            }
        }
    }

    private function seedPack(Game $game, SchoolGrade $grade, SchoolSubject $subject, int $weekday): void
    {
        $pack = WeekPlanQuestionBank::pack($grade, $subject, $weekday);

        $item = WeekPlanItem::query()->updateOrCreate(
            [
                'grade' => $grade,
                'week_number' => 1,
                'weekday' => $weekday,
                'subject' => $subject,
            ],
            [
                'level' => $weekday,
                'title' => $pack['title'],
                'game_slug' => GameType::MultipleChoice,
                'questions_per_round' => 5,
            ],
        );

        $questionIds = [];

        foreach ($pack['questions'] as $index => $row) {
            $code = sprintf(
                'g%d-w1-d%d-%s-%02d',
                $grade->value,
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
                    'format' => QuestionFormat::Choice,
                    'source' => GameType::MultipleChoice,
                    'subject' => $subject->favourite(),
                    'age_group' => null,
                    'grade' => $grade->value,
                    'locale' => 'ka',
                    'prompt' => $row['prompt'],
                    'hint' => null,
                    'media' => [
                        'emoji' => $row['emoji'],
                        'tile' => $subject->tile(),
                    ],
                    'payload' => ['choices' => $choices],
                    'answer' => ['key' => $correctKey],
                    'is_active' => true,
                ],
            );

            $questionIds[$question->id] = ['sort_order' => $index];
        }

        $item->questions()->sync($questionIds);
        $game->questions()->syncWithoutDetaching(array_keys($questionIds));
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
