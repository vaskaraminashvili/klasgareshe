<?php

namespace App\Data;

use App\Enums\QuestionPlayMode;

final readonly class ChoiceQuestionView
{
    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     * @param  list<array{char: string, blank: bool}>  $letters
     * @param  list<string>  $countItems
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
    ) {}

    /**
     * @return array{id: int, prompt: string, emoji: string, tile: string, playMode: string, letters: list<array{char: string, blank: bool}>, countItems: list<string>, choices: list<array{key: string, label: string, emoji: string}>}
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
        ];
    }
}
