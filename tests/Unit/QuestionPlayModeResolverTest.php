<?php

namespace Tests\Unit;

use App\Enums\QuestionPlayMode;
use App\Services\QuestionPlayModeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionPlayModeResolverTest extends TestCase
{
    #[DataProvider('prompts')]
    public function test_resolves_play_mode(
        string $prompt,
        string $correct,
        array $wrongs,
        QuestionPlayMode $mode,
        int $letterCount = 0,
        int $itemCount = 0,
    ): void {
        $shape = app(QuestionPlayModeResolver::class)->forPrompt($prompt, $correct, [$correct, ...$wrongs]);

        $this->assertSame($mode, $shape->mode);
        $this->assertCount($letterCount, $shape->letters);
        $this->assertCount($itemCount, $shape->countItems);
    }

    /**
     * @return list<array{0: string, 1: string, 2: list<string>, 3: QuestionPlayMode, 4?: int, 5?: int}>
     */
    public static function prompts(): array
    {
        return [
            ['კ_ტა — რომელი ასო აკლია?', 'ა', ['ე', 'ი', 'ო'], QuestionPlayMode::FillLetter, 4],
            ['დედ_ — რომელი ასო აკლია?', 'ა', ['ე', 'ი', 'ო'], QuestionPlayMode::FillLetter, 4],
            ['ა, ბ, გ — რომელი აკლია შუაში, თუ ა და გ გვაქვს?', 'ბ', ['დ', 'ე', 'ვ'], QuestionPlayMode::TapCorrect],
            ['დათვალე: 🍎🍎🍎 — რამდენია?', '3', ['2', '4', '5'], QuestionPlayMode::Count, 0, 3],
            ['დათვალე: 🌸 — რამდენია?', '1', ['0', '2', '3'], QuestionPlayMode::Count, 0, 1],
            ['დათვალე: ⭐×10 — რამდენია?', '10', ['5', '9', '11'], QuestionPlayMode::TapCorrect],
            ['დათვალე: 15 − 0 = ?', '15', ['14', '16', '10'], QuestionPlayMode::TapCorrect],
            ['დათვალე ათეულებამდე: 10 + 2 = ?', '12', ['11', '13', '20'], QuestionPlayMode::TapCorrect],
            ['რომელი ასოა „ე“?', 'ე', ['ა', 'ბ', 'გ'], QuestionPlayMode::TapCorrect],
            ['2 + 1 = ?', '3', ['2', '4', '1'], QuestionPlayMode::TapCorrect],
            ['რომელია ხილი?', 'ვაშლი', ['კატა', 'სახლი', 'წყალი'], QuestionPlayMode::Choice],
            ['რომელი წინადადებაა სწორი?', 'კატა სძინავს', ['კატა შვიდი', 'კატა ლურჯი', 'კატა მაგიდა'], QuestionPlayMode::Choice],
        ];
    }
}
