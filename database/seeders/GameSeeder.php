<?php

namespace Database\Seeders;

use App\Enums\FavouriteSubject;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use App\Models\Game;
use App\Models\Question;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (GameType::cases() as $type) {
            $defaults = $type->playDefaults();

            Game::query()->updateOrCreate(
                ['slug' => $type],
                [
                    'format' => $type->format(),
                    'lives' => $defaults['lives'],
                    'questions_per_round' => $defaults['questions_per_round'],
                    'xp_per_correct' => $defaults['xp_per_correct'],
                    'is_active' => true,
                ],
            );
        }

        foreach ($this->questions() as $question) {
            Question::query()->updateOrCreate(
                [
                    'source' => $question['source'],
                    'locale' => $question['locale'],
                    'prompt' => $question['prompt'],
                ],
                $question,
            );
        }
    }

    /**
     * Content copied from kidzio/game-*.html (and related page scripts).
     *
     * @return list<array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::MultipleChoice,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'en',
                'prompt' => 'What animal says "Moo"?',
                'hint' => null,
                'media' => ['emoji' => '🐄', 'tile' => 'tile-mint'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'Dog', 'emoji' => '🐶'],
                        ['key' => 'B', 'label' => 'Cow', 'emoji' => '🐄'],
                        ['key' => 'C', 'label' => 'Cat', 'emoji' => '🐱'],
                        ['key' => 'D', 'label' => 'Sheep', 'emoji' => '🐑'],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::Knowledge,
                'subject' => FavouriteSubject::Knowledge,
                'locale' => 'en',
                'prompt' => 'Which planet is called the Red Planet?',
                'hint' => 'Named after the Roman god of war, because of its rusty red color.',
                'media' => ['emoji' => '🪐', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'Earth', 'emoji' => '🌍'],
                        ['key' => 'B', 'label' => 'Mars', 'emoji' => '🔴'],
                        ['key' => 'C', 'label' => 'Saturn', 'emoji' => '🪐'],
                        ['key' => 'D', 'label' => 'Jupiter', 'emoji' => '🟠'],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::MatchAnimal,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'en',
                'prompt' => "Who's this?",
                'hint' => null,
                'media' => ['emoji' => '🦒', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'Zebra', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'Giraffe', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'Kangaroo', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'Horse', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::WhereLive,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'en',
                'prompt' => 'Where does a penguin live?',
                'hint' => "It's very cold and icy!",
                'media' => ['emoji' => '🐧', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'Jungle', 'emoji' => '🌴'],
                        ['key' => 'B', 'label' => 'Desert', 'emoji' => '🏜️'],
                        ['key' => 'C', 'label' => 'Antarctic', 'emoji' => '❄️'],
                        ['key' => 'D', 'label' => 'Farm', 'emoji' => '🌾'],
                    ],
                ],
                'answer' => ['key' => 'C'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::Opposites,
                'subject' => FavouriteSubject::Opposites,
                'locale' => 'en',
                'prompt' => 'Find the opposite of BIG',
                'hint' => null,
                'media' => ['emoji' => '🐘', 'tile' => 'tile-coral'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'TALL', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'SMALL', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'WIDE', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'HOT', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::GuessAnimal,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'en',
                'prompt' => 'Who am I?',
                'hint' => 'I have black and white stripes.',
                'media' => ['emoji' => '🦓', 'tile' => 'tile-mint'],
                'payload' => [
                    'clues' => [
                        'I have black and white stripes.',
                        "I live in Africa's savannah.",
                    ],
                    'choices' => [
                        ['key' => 'A', 'label' => 'Horse', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'Zebra', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'Tiger', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::TapCorrect,
                'subject' => FavouriteSubject::Math,
                'locale' => 'en',
                'prompt' => 'Which is a number?',
                'hint' => 'Numbers use digits like 0–9.',
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'A', 'emoji' => ''],
                        ['key' => 'B', 'label' => '7', 'emoji' => ''],
                        ['key' => 'C', 'label' => '', 'emoji' => '🐱'],
                        ['key' => 'D', 'label' => 'M', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::FillLetter,
                'subject' => FavouriteSubject::Alphabet,
                'locale' => 'en',
                'prompt' => 'Which letter is missing from C_T?',
                'hint' => null,
                'media' => ['emoji' => '🐱', 'tile' => 'tile-coral'],
                'payload' => [
                    'word' => 'CAT',
                    'blank_index' => 1,
                    'choices' => [
                        ['key' => 'A', 'label' => 'A', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'O', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'E', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'U', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'A'],
            ],
            [
                'format' => QuestionFormat::Count,
                'source' => GameType::Counting,
                'subject' => FavouriteSubject::Math,
                'locale' => 'en',
                'prompt' => 'Count the apples 🍎',
                'hint' => null,
                'media' => ['emoji' => '🍎', 'tile' => 'tile-coral'],
                'payload' => [
                    'item_emoji' => '🍎',
                    'count' => 8,
                    'choices' => [
                        ['key' => 'A', 'label' => '6', 'value' => 6],
                        ['key' => 'B', 'label' => '7', 'value' => 7],
                        ['key' => 'C', 'label' => '8', 'value' => 8],
                        ['key' => 'D', 'label' => '9', 'value' => 9],
                    ],
                ],
                'answer' => ['value' => 8],
            ],
            [
                'format' => QuestionFormat::Spell,
                'source' => GameType::SpellWord,
                'subject' => FavouriteSubject::Words,
                'locale' => 'en',
                'prompt' => 'Spell the word',
                'hint' => null,
                'media' => ['emoji' => '🍎', 'tile' => 'tile-sun'],
                'payload' => [
                    'letters' => ['L', 'E', 'A', 'P', 'N', 'T', 'S', 'O'],
                    'slots' => 5,
                ],
                'answer' => ['word' => 'APPLE'],
            ],
            [
                'format' => QuestionFormat::Pairs,
                'source' => GameType::MatchWord,
                'subject' => FavouriteSubject::Words,
                'locale' => 'en',
                'prompt' => 'Tap a word, then its picture',
                'hint' => 'A dog goes "woof"!',
                'media' => ['emoji' => '', 'tile' => 'tile-mint'],
                'payload' => [
                    'pairs' => [
                        ['key' => 'sun', 'label' => 'sun', 'emoji' => '☀️'],
                        ['key' => 'dog', 'label' => 'dog', 'emoji' => '🐶'],
                        ['key' => 'fish', 'label' => 'fish', 'emoji' => '🐟'],
                        ['key' => 'car', 'label' => 'car', 'emoji' => '🚗'],
                    ],
                ],
                'answer' => ['matches' => ['sun' => 'sun', 'dog' => 'dog', 'fish' => 'fish', 'car' => 'car']],
            ],
            [
                'format' => QuestionFormat::Pairs,
                'source' => GameType::ConnectPair,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'en',
                'prompt' => 'Connect the pair',
                'hint' => 'Tap a card on the left, then its match on the right.',
                'media' => ['emoji' => '', 'tile' => 'tile-sun'],
                'payload' => [
                    'left' => [
                        ['key' => 'bee', 'label' => 'Bee', 'emoji' => '🐝'],
                        ['key' => 'flower', 'label' => 'Flower', 'emoji' => '🌻'],
                        ['key' => 'fish', 'label' => 'Fish', 'emoji' => '🐟'],
                    ],
                    'right' => [
                        ['key' => 'honey', 'label' => 'Honey', 'emoji' => '🍯'],
                        ['key' => 'blossom', 'label' => 'Blossom', 'emoji' => '🌸'],
                        ['key' => 'water', 'label' => 'Water', 'emoji' => '🌊'],
                    ],
                ],
                'answer' => ['pairs' => ['bee' => 'honey', 'flower' => 'blossom', 'fish' => 'water']],
            ],
            [
                'format' => QuestionFormat::Grid,
                'source' => GameType::WordSearch,
                'subject' => FavouriteSubject::Words,
                'locale' => 'en',
                'prompt' => 'Word search',
                'hint' => 'Tap the first letter, then the last letter of a word.',
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'words' => ['CAT', 'SUN', 'DOG', 'APPLE', 'FISH'],
                    'cols' => 8,
                ],
                'answer' => ['words' => ['CAT', 'SUN', 'DOG', 'APPLE', 'FISH']],
            ],
            [
                'format' => QuestionFormat::Trace,
                'source' => GameType::TraceLetter,
                'subject' => FavouriteSubject::Alphabet,
                'locale' => 'en',
                'prompt' => 'Follow the dots to draw A',
                'hint' => null,
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'letter' => 'A',
                    'path' => 'M30 200 L100 30 L170 200 M60 140 L140 140',
                ],
                'answer' => ['letter' => 'A'],
            ],
            [
                'format' => QuestionFormat::Hotspot,
                'source' => GameType::BodyParts,
                'subject' => FavouriteSubject::Knowledge,
                'locale' => 'en',
                'prompt' => 'Tap the elbow',
                'hint' => null,
                'media' => ['emoji' => '', 'tile' => 'tile-sun'],
                'payload' => [
                    'prompt_part' => 'elbow',
                    'parts' => [
                        ['key' => 'eye', 'label' => 'Eye', 'emoji' => '👁️', 'x' => 90, 'y' => 45],
                        ['key' => 'elbow', 'label' => 'Elbow', 'emoji' => '💪', 'x' => 50, 'y' => 130],
                        ['key' => 'foot', 'label' => 'Foot', 'emoji' => '🦶', 'x' => 86, 'y' => 260],
                        ['key' => 'mouth', 'label' => 'Mouth', 'emoji' => '👄', 'x' => 100, 'y' => 65],
                        ['key' => 'hand', 'label' => 'Hand', 'emoji' => '✋', 'x' => 150, 'y' => 170],
                        ['key' => 'ear', 'label' => 'Ear', 'emoji' => '👂', 'x' => 125, 'y' => 50],
                    ],
                ],
                'answer' => ['part' => 'elbow'],
            ],
        ];
    }
}
