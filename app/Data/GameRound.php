<?php

namespace App\Data;

use App\Enums\GameType;

final readonly class GameRound
{
    /**
     * @param  list<int>  $questionIds
     */
    public function __construct(
        public GameType $game,
        public int $lives,
        public int $xpPerCorrect,
        public array $questionIds,
    ) {}
}
