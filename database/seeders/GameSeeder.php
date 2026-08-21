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
            $model = Question::query()->updateOrCreate(
                [
                    'source' => $question['source'],
                    'locale' => $question['locale'],
                ],
                $question,
            );

            $gameIds = [];

            $sourceGame = Game::query()->where('slug', $question['source'])->first();

            if ($sourceGame !== null) {
                $gameIds[] = $sourceGame->id;
            }

            if ($question['format'] === QuestionFormat::Choice) {
                $quickQuiz = Game::query()->where('slug', GameType::MultipleChoice)->first();

                if ($quickQuiz !== null) {
                    $gameIds[] = $quickQuiz->id;
                }
            }

            $model->games()->sync(array_values(array_unique($gameIds)));
        }

        Question::query()->where('locale', '!=', 'ka')->delete();
    }

    /**
     * Georgian (ka) content adapted from kidzio/game-*.html.
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
                'locale' => 'ka',
                'prompt' => 'რა ცხოველი ამბობს „მუ“?',
                'hint' => null,
                'media' => ['emoji' => '🐄', 'tile' => 'tile-mint'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'ძაღლი', 'emoji' => '🐶'],
                        ['key' => 'B', 'label' => 'ძროხა', 'emoji' => '🐄'],
                        ['key' => 'C', 'label' => 'კატა', 'emoji' => '🐱'],
                        ['key' => 'D', 'label' => 'ცხვარი', 'emoji' => '🐑'],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::Knowledge,
                'subject' => FavouriteSubject::Knowledge,
                'locale' => 'ka',
                'prompt' => 'რომელი პლანეტა ჰქვია წითელ პლანეტას?',
                'hint' => 'ომის რომაული ღმერთის სახელი აქვს, მისი მოწითალო ფერის გამო.',
                'media' => ['emoji' => '🪐', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'დედამიწა', 'emoji' => '🌍'],
                        ['key' => 'B', 'label' => 'მარსი', 'emoji' => '🔴'],
                        ['key' => 'C', 'label' => 'სატურნი', 'emoji' => '🪐'],
                        ['key' => 'D', 'label' => 'იუპიტერი', 'emoji' => '🟠'],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::MatchAnimal,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'ka',
                'prompt' => 'ვინ არის ეს?',
                'hint' => null,
                'media' => ['emoji' => '🦒', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'ზებრა', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'ჟირაფი', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'კენგურუ', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'ცხენი', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::WhereLive,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'ka',
                'prompt' => 'სად ცხოვრობს პინგვინი?',
                'hint' => 'ძალიან ცივა და ყინულია!',
                'media' => ['emoji' => '🐧', 'tile' => 'tile-sky'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'ჯუნგლები', 'emoji' => '🌴'],
                        ['key' => 'B', 'label' => 'უდაბნო', 'emoji' => '🏜️'],
                        ['key' => 'C', 'label' => 'ანტარქტიდა', 'emoji' => '❄️'],
                        ['key' => 'D', 'label' => 'ფერმა', 'emoji' => '🌾'],
                    ],
                ],
                'answer' => ['key' => 'C'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::Opposites,
                'subject' => FavouriteSubject::Opposites,
                'locale' => 'ka',
                'prompt' => 'იპოვე სიტყვის „დიდი“ საპირისპირო',
                'hint' => null,
                'media' => ['emoji' => '🐘', 'tile' => 'tile-coral'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'მაღალი', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'პატარა', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'ფართო', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'ცხელი', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::GuessAnimal,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'ka',
                'prompt' => 'ვინ ვარ მე?',
                'hint' => 'შავ-თეთრი ზოლები მაქვს.',
                'media' => ['emoji' => '🦓', 'tile' => 'tile-mint'],
                'payload' => [
                    'clues' => [
                        'შავ-თეთრი ზოლები მაქვს.',
                        'აფრიკის სავანაში ვცხოვრობ.',
                    ],
                    'choices' => [
                        ['key' => 'A', 'label' => 'ცხენი', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'ზებრა', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'ვეფხვი', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::TapCorrect,
                'subject' => FavouriteSubject::Math,
                'locale' => 'ka',
                'prompt' => 'რომელი არის რიცხვი?',
                'hint' => 'რიცხვები ციფრებით იწერება, მაგალითად 0–9.',
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'choices' => [
                        ['key' => 'A', 'label' => 'ა', 'emoji' => ''],
                        ['key' => 'B', 'label' => '7', 'emoji' => ''],
                        ['key' => 'C', 'label' => '', 'emoji' => '🐱'],
                        ['key' => 'D', 'label' => 'მ', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'B'],
            ],
            [
                'format' => QuestionFormat::Choice,
                'source' => GameType::FillLetter,
                'subject' => FavouriteSubject::Alphabet,
                'locale' => 'ka',
                'prompt' => 'რომელი ასო აკლია სიტყვას კ_ტა?',
                'hint' => null,
                'media' => ['emoji' => '🐱', 'tile' => 'tile-coral'],
                'payload' => [
                    'word' => 'კატა',
                    'blank_index' => 1,
                    'choices' => [
                        ['key' => 'A', 'label' => 'ა', 'emoji' => ''],
                        ['key' => 'B', 'label' => 'ო', 'emoji' => ''],
                        ['key' => 'C', 'label' => 'ე', 'emoji' => ''],
                        ['key' => 'D', 'label' => 'უ', 'emoji' => ''],
                    ],
                ],
                'answer' => ['key' => 'A'],
            ],
            [
                'format' => QuestionFormat::Count,
                'source' => GameType::Counting,
                'subject' => FavouriteSubject::Math,
                'locale' => 'ka',
                'prompt' => 'დათვალე ვაშლები 🍎',
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
                'locale' => 'ka',
                'prompt' => 'დაწერე სიტყვა',
                'hint' => null,
                'media' => ['emoji' => '🍎', 'tile' => 'tile-sun'],
                'payload' => [
                    'letters' => ['შ', 'ე', 'ვ', 'ლ', 'ნ', 'ა', 'ს', 'ი'],
                    'slots' => 5,
                ],
                'answer' => ['word' => 'ვაშლი'],
            ],
            [
                'format' => QuestionFormat::Pairs,
                'source' => GameType::MatchWord,
                'subject' => FavouriteSubject::Words,
                'locale' => 'ka',
                'prompt' => 'შეეხე სიტყვას, შემდეგ მის სურათს',
                'hint' => 'ძაღლი ამბობს „ჰაფ“!',
                'media' => ['emoji' => '', 'tile' => 'tile-mint'],
                'payload' => [
                    'pairs' => [
                        ['key' => 'sun', 'label' => 'მზე', 'emoji' => '☀️'],
                        ['key' => 'dog', 'label' => 'ძაღლი', 'emoji' => '🐶'],
                        ['key' => 'fish', 'label' => 'თევზი', 'emoji' => '🐟'],
                        ['key' => 'car', 'label' => 'მანქანა', 'emoji' => '🚗'],
                    ],
                ],
                'answer' => ['matches' => ['sun' => 'sun', 'dog' => 'dog', 'fish' => 'fish', 'car' => 'car']],
            ],
            [
                'format' => QuestionFormat::Pairs,
                'source' => GameType::ConnectPair,
                'subject' => FavouriteSubject::Animals,
                'locale' => 'ka',
                'prompt' => 'დააკავშირე წყვილი',
                'hint' => 'შეეხე მარცხენა ბარათს, შემდეგ მის წყვილს მარჯვნივ.',
                'media' => ['emoji' => '', 'tile' => 'tile-sun'],
                'payload' => [
                    'left' => [
                        ['key' => 'bee', 'label' => 'ფუტკარი', 'emoji' => '🐝'],
                        ['key' => 'flower', 'label' => 'ყვავილი', 'emoji' => '🌻'],
                        ['key' => 'fish', 'label' => 'თევზი', 'emoji' => '🐟'],
                    ],
                    'right' => [
                        ['key' => 'honey', 'label' => 'თაფლი', 'emoji' => '🍯'],
                        ['key' => 'blossom', 'label' => 'ყვავილობა', 'emoji' => '🌸'],
                        ['key' => 'water', 'label' => 'წყალი', 'emoji' => '🌊'],
                    ],
                ],
                'answer' => ['pairs' => ['bee' => 'honey', 'flower' => 'blossom', 'fish' => 'water']],
            ],
            [
                'format' => QuestionFormat::Grid,
                'source' => GameType::WordSearch,
                'subject' => FavouriteSubject::Words,
                'locale' => 'ka',
                'prompt' => 'იპოვე სიტყვები',
                'hint' => 'შეეხე სიტყვის პირველ ასოს, შემდეგ ბოლო ასოს.',
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'words' => ['კატა', 'მზე', 'ძაღლი', 'ვაშლი', 'თევზი'],
                    'cols' => 8,
                ],
                'answer' => ['words' => ['კატა', 'მზე', 'ძაღლი', 'ვაშლი', 'თევზი']],
            ],
            [
                'format' => QuestionFormat::Trace,
                'source' => GameType::TraceLetter,
                'subject' => FavouriteSubject::Alphabet,
                'locale' => 'ka',
                'prompt' => 'მიჰყევი წერტილებს და დახატე ასო ა',
                'hint' => null,
                'media' => ['emoji' => '', 'tile' => 'tile-violet'],
                'payload' => [
                    'letter' => 'ა',
                    'path' => 'M30 200 L100 30 L170 200 M60 140 L140 140',
                ],
                'answer' => ['letter' => 'ა'],
            ],
            [
                'format' => QuestionFormat::Hotspot,
                'source' => GameType::BodyParts,
                'subject' => FavouriteSubject::Knowledge,
                'locale' => 'ka',
                'prompt' => 'შეეხე იდაყვს',
                'hint' => null,
                'media' => ['emoji' => '', 'tile' => 'tile-sun'],
                'payload' => [
                    'prompt_part' => 'elbow',
                    'parts' => [
                        ['key' => 'eye', 'label' => 'თვალი', 'emoji' => '👁️', 'x' => 90, 'y' => 45],
                        ['key' => 'elbow', 'label' => 'იდაყვი', 'emoji' => '💪', 'x' => 50, 'y' => 130],
                        ['key' => 'foot', 'label' => 'ფეხი', 'emoji' => '🦶', 'x' => 86, 'y' => 260],
                        ['key' => 'mouth', 'label' => 'პირი', 'emoji' => '👄', 'x' => 100, 'y' => 65],
                        ['key' => 'hand', 'label' => 'ხელი', 'emoji' => '✋', 'x' => 150, 'y' => 170],
                        ['key' => 'ear', 'label' => 'ყური', 'emoji' => '👂', 'x' => 125, 'y' => 50],
                    ],
                ],
                'answer' => ['part' => 'elbow'],
            ],
        ];
    }
}
