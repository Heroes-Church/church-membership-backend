<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::query()->latest('id')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->latest('id')->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(User $user, array $data): User
    {
        if (array_key_exists('password', $data) && blank($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
