<?php

namespace App\Enums;

enum FavouriteSubject: string
{
    case Math = 'math';
    case Alphabet = 'alphabet';
    case Animals = 'animals';
    case Words = 'words';
    case Knowledge = 'knowledge';
    case Opposites = 'opposites';

    /**
     * Onboarding topic keys that map onto a Learn subject.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'world' => 'knowledge',
        'science' => 'knowledge',
    ];
}
