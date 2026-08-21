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
}
