<?php

namespace App\Data;

use App\Enums\QuestionPlayMode;

final readonly class QuestionPlayShape
{
    /**
     * @param  list<array{char: string, blank: bool}>  $letters
     * @param  list<string>  $countItems
     */
    public function __construct(
        public QuestionPlayMode $mode,
        public array $letters = [],
        public array $countItems = [],
    ) {}
}
