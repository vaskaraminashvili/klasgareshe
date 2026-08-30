<?php

namespace App\Data;

final readonly class ChoiceQuestionView
{
    /**
     * @param  list<array{key: string, label: string, emoji: string}>  $choices
     */
    public function __construct(
        public int $id,
        public string $prompt,
        public string $emoji,
        public string $tile,
        public array $choices,
    ) {}

    /**
     * @return array{id: int, prompt: string, emoji: string, tile: string, choices: list<array{key: string, label: string, emoji: string}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'emoji' => $this->emoji,
            'tile' => $this->tile,
            'choices' => $this->choices,
        ];
    }
}
