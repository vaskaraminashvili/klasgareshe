<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function findOrFail(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function authenticated(): User
    {
        $id = Auth::id();

        if (! is_numeric($id)) {
            throw new AuthenticationException;
        }

        return $this->findOrFail((int) $id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function nicknameExists(string $nickname, ?int $exceptUserId = null): bool
    {
        $query = User::withTrashed()->where('nickname', $nickname);

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        return $query->exists();
    }

    public function emailTaken(string $email, ?int $exceptUserId = null): bool
    {
        $email = mb_strtolower(trim($email));

        $query = User::withTrashed()
            ->where(function ($inner) use ($email): void {
                $inner->whereRaw('lower(email) = ?', [$email])
                    ->orWhereRaw('lower(pending_parent_email) = ?', [$email]);
            });

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        return $query->exists();
    }

    public function findByNickname(string $nickname): ?User
    {
        return User::query()->where('nickname', $nickname)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower($email)])
            ->first();
    }

    public function rotateRememberToken(User $user): User
    {
        $user->setRememberToken(Str::random(60));
        $user->save();

        return $user->fresh() ?? $user;
    }

    public function deleteOtherSessions(User $user, string $keepSessionId): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $keepSessionId)
            ->delete();
    }

    public function markEmailVerified(User $user): User
    {
        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return $user->fresh() ?? $user;
    }

    /**
     * Parents who finished setup and verified email — weekly report mail candidates.
     *
     * @return Collection<int, User>
     */
    public function verifiedLearners(): Collection
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('onboarding_completed_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Soft-deleted accounts whose grace window has elapsed.
     *
     * @return Collection<int, User>
     */
    public function dueForPurge(CarbonInterface $requestedBefore): Collection
    {
        return User::onlyTrashed()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', $requestedBefore)
            ->orderBy('id')
            ->get();
    }

    public function softDelete(User $user): User
    {
        $user->delete();

        return $user;
    }

    public function forceDelete(User $user): void
    {
        $user->forceDelete();
    }

    public function deleteSessions(User $user): void
    {
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();
    }
}
