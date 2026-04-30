<?php

namespace App\Services;

use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class AdministratorService
{
    public function index(): array
    {
        $rows = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [UserTypeEnum::ADMIN, UserTypeEnum::SUPER_ADMIN]);
            })
            ->orderBy('name')
            ->get();

        return [
            'administrators' => $rows->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->hasRole(UserTypeEnum::SUPER_ADMIN)
                    ? UserTypeEnum::SUPER_ADMIN
                    : UserTypeEnum::ADMIN,
            ])->values()->all(),
        ];
    }

    public function store(array $validatedData): User
    {
        $roleName = $validatedData['role'] ?? UserTypeEnum::ADMIN;
        if (! in_array($roleName, [UserTypeEnum::ADMIN, UserTypeEnum::SUPER_ADMIN], true)) {
            $roleName = UserTypeEnum::ADMIN;
        }

        $admin = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
        ]);

        $admin->syncRoles([$roleName]);

        return $admin;
    }

    public function update(array $validatedData): void
    {
        $admin = User::findOrFail($validatedData['id']);

        if (! empty($validatedData['password'])) {
            $admin->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ]);
        } else {
            $admin->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
            ]);
        }
    }

    public function destroy(string $id): void
    {
        $user = User::findOrFail($id);

        if ((int) Auth::id() === (int) $user->id) {
            throw new AuthorizationException('You cannot delete your own account.');
        }

        if ($user->hasRole(UserTypeEnum::SUPER_ADMIN)) {
            $count = User::whereHas('roles', fn ($q) => $q->where('name', UserTypeEnum::SUPER_ADMIN))->count();
            if ($count <= 1) {
                throw new AuthorizationException('Cannot delete the last super administrator.');
            }
        }

        User::destroy($id);
    }

    public function restore($id): void
    {
        User::withTrashed()->findOrFail($id)->restore();
    }
}
