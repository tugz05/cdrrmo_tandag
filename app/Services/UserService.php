<?php

namespace App\Services;

use App\Enums\AppMobileRole;
use App\Enums\UserTypeEnum;
use App\Helpers\JHelper;
use App\Models\User;

class UserService
{
    public function all()
    {
        return [
            'users' => User::whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', [
                    UserTypeEnum::ADMIN,
                    UserTypeEnum::SUPER_ADMIN,
                ]);
            })
                ->latest()
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'email' => $user->email,
                        'fullname' => $user->full_name,
                        'confirmed_at' => $user->confirmed_at,
                        'image' => $user->image,
                        'latitude' => $user->latitude,
                        'longitude' => $user->longitude,
                        'address' => $user->address,
                        'phone' => $user->phone,
                        'created_at' => $user->created_at,
                    ];
                }),
        ];
    }

    public function show(User $user)
    {
        $user['fullname'] = $user->full_name;
        $user['valid_ids'] = JHelper::getValidImages($user->id);

        return response()->json($user);
    }

    public function store(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'app_role' => AppMobileRole::Citizen,
        ]);

        $user->addRole('user');

        return $user;
    }

    public function verify($id)
    {
        $user = User::find($id, ['id', 'confirmed_at']);

        $user->confirmed_at = is_null($user->confirmed_at)
            ? now()
            : null;

        return $user->save();
    }

    public function destroy($id)
    {
        User::destroy($id);
    }

    public function restore($id)
    {
        User::withTrashed()->find($id)->restore();
    }
}
