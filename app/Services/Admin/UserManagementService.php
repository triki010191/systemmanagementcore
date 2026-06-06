<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function paginate(?string $role = null, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($role, fn ($q) => $q->role($role))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, Role> */
    public function roleOptions(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        return $user->fresh(['roles']);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
