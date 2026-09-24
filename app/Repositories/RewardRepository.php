<?php

namespace App\Repositories;

use App\Enums\RewardClaimType;
use App\Models\RewardClaim;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class RewardRepository
{
    public function exists(User $user, RewardClaimType $type, string $reference): bool
    {
        return RewardClaim::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('reference', $reference)
            ->exists();
    }

    /**
     * Insert the claim row once. Returns false when it was already claimed.
     */
    public function recordOnce(User $user, RewardClaimType $type, string $reference): bool
    {
        try {
            return DB::transaction(function () use ($user, $type, $reference): bool {
                $locked = RewardClaim::query()
                    ->where('user_id', $user->id)
                    ->where('type', $type)
                    ->where('reference', $reference)
                    ->lockForUpdate()
                    ->exists();

                if ($locked) {
                    return false;
                }

                RewardClaim::query()->create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'reference' => $reference,
                    'claimed_at' => now(),
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
