<?php

namespace App\Services;

use App\Data\QuestionPlayShape;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use App\Enums\QuestionPlayMode;
use App\Models\Question;
use InvalidArgumentException;

class QuestionPlayModeResolver
{
    public function playerRoute(GameType $type): string
    {
        return $type->playerRoute();
    }

    public function forQuestion(Question $question): QuestionPlayShape
    {
        $choices = $question->choices();
        $labels = [];

        foreach ($choices as $choice) {
            $labels[] = $choice['label'];
        }

        $correctLabel = $this->correctLabel($question, $choices);

        if ($question->format === QuestionFormat::Count) {
            $items = $question->countItems();

            if ($items !== []) {
                return new QuestionPlayShape(QuestionPlayMode::Count, countItems: $items);
            }
        }

        if ($question->source === GameType::TapCorrect) {
            return new QuestionPlayShape(QuestionPlayMode::TapCorrect);
        }

        return $this->forPrompt((string) $question->prompt, $correctLabel, $labels);
    }

    /**
     * @param  list<string>  $choiceLabels
     */
    public function forPrompt(string $prompt, string $correctLabel, array $choiceLabels): QuestionPlayShape
    {
        $letters = $this->fillLetters($prompt);

        if ($letters !== null && $this->allSingleLetters([$correctLabel, ...$choiceLabels])) {
            return new QuestionPlayShape(QuestionPlayMode::FillLetter, letters: $letters);
        }

        $countItems = $this->countItems($prompt, $correctLabel);

        if ($countItems !== null) {
            return new QuestionPlayShape(QuestionPlayMode::Count, countItems: $countItems);
        }

        if ($this->isTapCorrect($choiceLabels)) {
            return new QuestionPlayShape(QuestionPlayMode::TapCorrect);
        }

        return new QuestionPlayShape(QuestionPlayMode::Choice);
    }

    /**
     * @return list<array{char: string, blank: bool}>|null
     */
    private function fillLetters(string $prompt): ?array
    {
        if (preg_match('/(\S*_\S*)/u', $prompt, $match) !== 1) {
            return null;
        }

        $letters = [];
        $blanks = 0;

        foreach (mb_str_split($match[1]) as $char) {
            if ($char === '_') {
                $letters[] = ['char' => '?', 'blank' => true];
                $blanks++;

                continue;
            }

            $letters[] = ['char' => $char, 'blank' => false];
        }

        if ($blanks !== 1 || count($letters) < 2) {
            return null;
        }

        return $letters;
    }

    /**
     * @return list<string>|null
     */
    private function countItems(string $prompt, string $correctLabel): ?array
    {
        if (preg_match('/^დათვალე:\s*(.+?)\s*—\s*რამდენია/u', $prompt, $match) !== 1) {
            return null;
        }

        preg_match_all('/\X/u', trim($match[1]), $raw);

        $glyphs = [];

        foreach ($raw[0] as $glyph) {
            if (trim($glyph) === '') {
                continue;
            }

            $glyphs[] = $glyph;
        }

        if ($glyphs === [] || count($glyphs) > 12) {
            return null;
        }

        $first = $glyphs[0];

        if (preg_match('/^[\d×x+\-−=]$/u', $first) === 1) {
            return null;
        }

        foreach ($glyphs as $glyph) {
            if ($glyph !== $first) {
                return null;
            }
        }

        if ((string) count($glyphs) !== $correctLabel) {
            return null;
        }

        return $glyphs;
    }

    /**
     * @param  list<string>  $labels
     */
    private function isTapCorrect(array $labels): bool
    {
        if ($labels === []) {
            return false;
        }

        $numeric = true;
        $max = 0;

        foreach ($labels as $label) {
            $len = mb_strlen($label);
            $max = max($max, $len);

            if (preg_match('/^\d{1,3}$/u', $label) !== 1) {
                $numeric = false;
            }
        }

        if ($numeric && $max <= 3) {
            return true;
        }

        return $max === 1;
    }

    /**
     * @param  list<string>  $labels
     */
    private function allSingleLetters(array $labels): bool
    {
        foreach ($labels as $label) {
            if (mb_strlen($label) !== 1) {
                return false;
            }
        }

        return $labels !== [];
    }

    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     */
    private function correctLabel(Question $question, array $choices): string
    {
        try {
            $key = $question->correctKey();
        } catch (InvalidArgumentException) {
            return (string) ($question->answer['value'] ?? '');
        }

        foreach ($choices as $choice) {
            if ($choice['key'] === $key) {
                return $choice['label'];
            }
        }

        return (string) ($question->answer['value'] ?? '');
    }
}
