<?php

namespace App\Repositories;

use App\Models\SearchQuery;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchQueryRepository
{
    public function record(User $user, string $query, string $normalized): void
    {
        $row = SearchQuery::query()
            ->where('user_id', $user->id)
            ->where('normalized', $normalized)
            ->first();

        if ($row === null) {
            SearchQuery::query()->create([
                'user_id' => $user->id,
                'query' => $query,
                'normalized' => $normalized,
                'hits' => 1,
            ]);

            return;
        }

        $row->update([
            'query' => $query,
            'hits' => $row->hits + 1,
        ]);
    }

    /**
     * Latest distinct queries for this kid, newest first.
     *
     * @return list<string>
     */
    public function recentFor(User $user, int $limit = 6): array
    {
        $queries = [];

        foreach (SearchQuery::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('query') as $query) {
            $queries[] = (string) $query;
        }

        return $queries;
    }

    /**
     * Queries ordered by how often they have been run, across every account.
     *
     * @return list<string>
     */
    public function popular(int $limit = 8): array
    {
        $queries = [];

        foreach (SearchQuery::query()
            ->select('normalized')
            ->selectRaw('MAX(query) as query')
            ->selectRaw('SUM(hits) as total')
            ->groupBy('normalized')
            ->orderByDesc('total')
            ->orderBy('normalized')
            ->limit($limit)
            ->get() as $row) {
            $queries[] = (string) $row->query;
        }

        return $queries;
    }

    /**
     * @return Collection<int, SearchQuery>
     */
    public function forUser(User $user): Collection
    {
        return SearchQuery::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
    }
}
