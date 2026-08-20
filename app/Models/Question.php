<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\FavouriteSubject;
use App\Enums\GameType;
use App\Enums\QuestionFormat;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property QuestionFormat $format
 * @property GameType|null $source
 * @property FavouriteSubject $subject
 * @property AgeGroup|null $age_group
 * @property string $locale
 * @property string|null $prompt
 * @property string|null $hint
 * @property array<string, mixed>|null $media
 * @property array<string, mixed> $payload
 * @property array<string, mixed> $answer
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'format',
    'source',
    'subject',
    'age_group',
    'locale',
    'prompt',
    'hint',
    'media',
    'payload',
    'answer',
    'is_active',
])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format' => QuestionFormat::class,
            'source' => GameType::class,
            'subject' => FavouriteSubject::class,
            'age_group' => AgeGroup::class,
            'media' => 'array',
            'payload' => 'array',
            'answer' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<array{key: string, label: string, emoji: string}>
     */
    public function choices(): array
    {
        $raw = $this->payload['choices'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $choices = [];

        foreach ($raw as $choice) {
            if (! is_array($choice) || ! isset($choice['key'], $choice['label'])) {
                continue;
            }

            $choices[] = [
                'key' => (string) $choice['key'],
                'label' => (string) $choice['label'],
                'emoji' => isset($choice['emoji']) ? (string) $choice['emoji'] : '',
            ];
        }

        return $choices;
    }

    public function correctKey(): string
    {
        $key = $this->answer['key'] ?? null;

        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('Question is missing a choice answer key.');
        }

        return $key;
    }

    public function mediaEmoji(): string
    {
        $emoji = $this->media['emoji'] ?? '';

        return is_string($emoji) ? $emoji : '';
    }

    public function mediaTile(): string
    {
        $tile = $this->media['tile'] ?? 'tile-mint';

        return is_string($tile) && $tile !== '' ? $tile : 'tile-mint';
    }
}
