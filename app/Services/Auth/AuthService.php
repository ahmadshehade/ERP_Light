<?php

namespace App\Services\Auth;

use App\Enums\NameOfCache;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class AuthService
{
    /**
     * Register a new user
     *
     * @param array $data
     * @return array ['user' => User, 'token' => string]
     * @throws \Exception
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $data['password'] = Hash::make($data['password']);

            $user = User::create($data);

            $user->profile()->create([
                'phone' => $data['phone'],
                'timezone' => $data['timezone'] ?? 'UTC',
                'language' => $data['language'] ?? 'en',
                'birth_date' => $data['birth_date'] ?? null,
            ]);
            DB::afterCommit(function ()  use ($user) {
                Cache::tags([NameOfCache::PROFILE->value])->flush();
            });

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load('profile'),
                'token' => $token,
            ];
        });
    }

    /**
     * Login user and generate token
     *
     * @param array $data
     * @return array|null
     */
    public function login(array $data): ?array
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw new BusinessRuleException('The provided credentials are incorrect.', 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user (delete all tokens)
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool
    {

        return $user->tokens()->delete() > 0;
    }
}
