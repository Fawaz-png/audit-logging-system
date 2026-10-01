<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserService
{
    public function provision(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'uuid' => (string) str()->uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'department_id' => $data['department_id'],
                'role_id' => $data['role_id'],
                'password' => Hash::make(str()->random(64)),
                'active' => false,
            ]);

            /** @var PasswordBroker $broker */
            $broker = Password::broker('users');

            $token = $broker->createToken($user);

            $user->sendAccountActivationNotification($token);

            return $user;
        });
    }
}
