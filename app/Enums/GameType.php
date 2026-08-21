<?php

namespace App\Enums;

enum GameType: string
{
    case MultipleChoice = 'multiple-choice';
    case TapCorrect = 'tap-correct';
    case Counting = 'counting';
    case TraceLetter = 'trace-letter';
    case FillLetter = 'fill-letter';
    case SpellWord = 'spell-word';
    case MatchWord = 'match-word';
    case MatchAnimal = 'match-animal';
    case GuessAnimal = 'guess-animal';
    case WordSearch = 'word-search';
    case ConnectPair = 'connect-pair';
    case Opposites = 'opposites';
    case BodyParts = 'body-parts';
    case WhereLive = 'where-live';
    case Knowledge = 'knowledge';

    public function format(): QuestionFormat
    {
        return match ($this) {
            self::MultipleChoice,
            self::TapCorrect,
            self::FillLetter,
            self::MatchAnimal,
            self::GuessAnimal,
            self::Opposites,
            self::WhereLive,
            self::Knowledge => QuestionFormat::Choice,
            self::Counting => QuestionFormat::Count,
            self::SpellWord => QuestionFormat::Spell,
            self::MatchWord,
            self::ConnectPair => QuestionFormat::Pairs,
            self::WordSearch => QuestionFormat::Grid,
            self::TraceLetter => QuestionFormat::Trace,
            self::BodyParts => QuestionFormat::Hotspot,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::MultipleChoice => __('games.multiple_choice'),
            self::TapCorrect => __('games.tap_correct'),
            self::Counting => __('games.counting'),
            self::TraceLetter => __('games.trace_letter'),
            self::FillLetter => __('games.fill_letter'),
            self::SpellWord => __('games.spell_word'),
            self::MatchWord => __('games.match_word'),
            self::MatchAnimal => __('games.match_animal'),
            self::GuessAnimal => __('games.guess_animal'),
            self::WordSearch => __('games.word_search'),
            self::ConnectPair => __('games.connect_pair'),
            self::Opposites => __('games.opposites'),
            self::BodyParts => __('games.body_parts'),
            self::WhereLive => __('games.where_live'),
            self::Knowledge => __('games.knowledge'),
        };
    }

    public function routeName(): string
    {
        return 'game-'.$this->value;
    }

    /**
     * @return array{lives: int, questions_per_round: int, xp_per_correct: int}
     */
    public function playDefaults(): array
    {
        return match ($this) {
            self::MultipleChoice => [
                'lives' => 3,
                'questions_per_round' => 10,
                'xp_per_correct' => 8,
            ],
            default => [
                'lives' => 3,
                'questions_per_round' => 10,
                'xp_per_correct' => 8,
            ],
        };
    }
}
