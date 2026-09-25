<?php

namespace App\Data;

use App\Enums\QuestionPlayMode;

final readonly class ChoiceQuestionView
{
    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     * @param  list<array{char: string, blank: bool}>  $letters
     * @param  list<string>  $countItems
     * @param  list<string>  $keyboard
     * @param  list<list<array{0: float, 1: float}>>  $strokes
     */
    public function __construct(
        public int $id,
        public string $prompt,
        public string $emoji,
        public string $tile,
        public array $choices,
        public QuestionPlayMode $playMode = QuestionPlayMode::Choice,
        public array $letters = [],
        public array $countItems = [],
        public string $hint = '',
        public array $keyboard = [],
        public array $strokes = [],
        public string $pairLabel = '',
        public string $pairEmoji = '',
        public int $slots = 0,
    ) {}

    /**
     * @return array{id: int, prompt: string, emoji: string, tile: string, playMode: string, letters: list<array{char: string, blank: bool}>, countItems: list<string>, choices: list<array{key: string, label: string, emoji: string}>, hint: string, keyboard: list<string>, strokes: list<list<array{0: float, 1: float}>>, pairLabel: string, pairEmoji: string, slots: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'emoji' => $this->emoji,
            'tile' => $this->tile,
            'playMode' => $this->playMode->value,
            'letters' => $this->letters,
            'countItems' => $this->countItems,
            'choices' => $this->choices,
            'hint' => $this->hint,
            'keyboard' => $this->keyboard,
            'strokes' => $this->strokes,
            'pairLabel' => $this->pairLabel,
            'pairEmoji' => $this->pairEmoji,
            'slots' => $this->slots,
        ];
    }
}
