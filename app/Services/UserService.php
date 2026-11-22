<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Get all users belonging to the current church.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::query()
            ->latest('id')
            ->get();
    }

    /**
     * Get a paginated list of users.
     */
    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator {
        return User::query()
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Create a user with the default Guest role.
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create($data);

            $user->assignRole('Guest');

            return $user->load('roles');
        });
    }

    /**
     * Update a user's account information.
     *
     * Revoke authentication tokens when the
     * account password changes.
     */
    public function update(User $user, array $data): User
    {
        if (
            array_key_exists('password', $data)
            && blank($data['password'])
        ) {
            unset($data['password']);
        }

        $passwordUpdated = array_key_exists(
            'password',
            $data
        );

        return DB::transaction(
            function () use (
                $user,
                $data,
                $passwordUpdated
            ): User {
                $user->update($data);

                if ($passwordUpdated) {
                    $user->tokens()->delete();
                }

                return $user->refresh();
            }
        );
    }

    /**
     * Block permanent deletion until safe account
     * deactivation and data retention are implemented.
     */
    public function delete(User $user): void
    {
        abort(
            409,
            'Permanent account deletion is disabled. '
            . 'Use account deactivation once available.'
        );
    }
}
