<?php

namespace App\Enums;

enum FavouriteSubject: string
{
    case Georgian = 'georgian';
    case Math = 'math';
    case History = 'history';
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
        'world' => 'history',
        'science' => 'history',
        'alphabet' => 'georgian',
        'words' => 'georgian',
        'knowledge' => 'history',
    ];

    /**
     * @return list<self>
     */
    public static function schoolSubjects(): array
    {
        return [
            self::Georgian,
            self::Math,
            self::History,
        ];
    }
}
