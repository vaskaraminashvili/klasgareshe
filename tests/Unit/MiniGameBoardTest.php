<?php

namespace Tests\Unit;

use App\Services\WordSearchBoard;
use PHPUnit\Framework\TestCase;

class MiniGameBoardTest extends TestCase
{
    public function test_word_search_places_georgian_words_on_the_first_rows(): void
    {
        $board = new WordSearchBoard;
        $built = $board->build(['კატა', 'მზე']);

        $this->assertSame('კ', $built['rows'][0][0]);
        $this->assertSame('ა', $built['rows'][0][3]);
        $this->assertSame('კატა', $board->lettersBetween($built['rows'], 0, 0, 0, 3));
        $this->assertSame('ეზმ', $board->lettersBetween($built['rows'], 1, 2, 1, 0));
    }
}
