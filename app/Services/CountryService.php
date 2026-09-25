<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\UserStatRepository;
use App\Support\CountryCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class CountryService
{
    public function __construct(
        private UserRepository $users,
        private UserStatRepository $stats,
    ) {}

    public function choose(User $user, ?string $code): void
    {
        $code = $code === null ? null : strtolower(trim($code));

        if ($code === '') {
            $code = null;
        }

        if ($code !== null && CountryCatalog::find($code) === null) {
            throw ValidationException::withMessages([
                'country' => (string) __('country.errors.unknown'),
            ]);
        }

        $this->users->update($user, ['country' => $code]);
    }

    /**
     * @return array{
     *     selected: ?string,
     *     selectedName: string,
     *     selectedEmoji: string,
     *     yourRank: ?int,
     *     learnersHere: int,
     *     countryCount: int,
     *     climbers: list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}>,
     *     countries: list<array{code: string, emoji: string, name: string, continent: string, keywords: string, learners: int, weekXp: int}>
     * }
     */
    public function page(User $user): array
    {
        $counts = $this->users->visibleCountsByCountry();
        $week = $this->weekTotals();
        $countries = [];

        foreach (CountryCatalog::all() as $row) {
            $countries[] = [
                'code' => $row['code'],
                'emoji' => $row['emoji'],
                'name' => CountryCatalog::name($row['code']),
                'continent' => $row['continent'],
                'keywords' => $row['keywords'],
                'learners' => $counts[$row['code']] ?? 0,
                'weekXp' => $week[$row['code']] ?? 0,
            ];
        }

        $climbers = $countries;
        usort($climbers, static fn (array $a, array $b): int => $b['weekXp'] <=> $a['weekXp']);
        $climbers = array_values(array_filter($climbers, static fn (array $row): bool => $row['weekXp'] > 0));
        $climbers = array_slice($climbers, 0, 3);

        $selected = is_string($user->country) && $user->country !== '' ? $user->country : null;

        return [
            'selected' => $selected,
            'selectedName' => CountryCatalog::name($selected),
            'selectedEmoji' => $selected === null ? '🌎' : CountryCatalog::emoji($selected),
            'yourRank' => $selected === null ? null : $this->stats->rankInCountry($user),
            'learnersHere' => $selected === null ? $this->stats->countLearners() : $this->stats->countInCountry($selected),
            'countryCount' => count($countries),
            'climbers' => $climbers,
            'countries' => $countries,
        ];
    }

    /**
     * @return list<array{code: string, emoji: string, name: string, learners: int}>
     */
    public function topByLearners(int $limit = 4): array
    {
        $counts = $this->users->visibleCountsByCountry();
        arsort($counts);
        $rows = [];

        foreach ($counts as $code => $learners) {
            if ($learners < 1 || CountryCatalog::find($code) === null) {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'emoji' => CountryCatalog::emoji($code),
                'name' => CountryCatalog::name($code),
                'learners' => $learners,
            ];

            if (count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    private function weekTotals(): array
    {
        $start = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);

        return $this->stats->weekXpByCountry($start->toDateString(), $start->addDays(6)->toDateString());
    }
}
