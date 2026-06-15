<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagement
{
    public static function roles(): array
    {
        return [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'member' => 'Member',
        ];
    }

    public static function baseRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(array_keys(self::roles()))],
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ];
    }

    public static function storeRules(): array
    {
        return array_merge(self::baseRules(), [
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
    }

    public static function updateRules(User $user, bool $includePassword = false): array
    {
        $rules = self::baseRules($user);

        if ($includePassword) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public static function assignmentRules(): array
    {
        return [
            'role' => ['required', Rule::in(array_keys(self::roles()))],
            'team_id' => ['nullable', Rule::exists('teams', 'id')],
        ];
    }
}
