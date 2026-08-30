<?php

namespace App\Data;

final readonly class FriendsProfileStrip
{
    /**
     * @param  list<string>  $avatars
     */
    public function __construct(
        public int $count,
        public int $onlineCount,
        public int $beatingCount,
        public array $avatars,
    ) {}
}
