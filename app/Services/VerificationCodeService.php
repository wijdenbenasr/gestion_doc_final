<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Support\Facades\Hash;

class VerificationCodeService
{
    public function sendForUser(User $user, int $ttlMinutes = 30): void
    {
        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );

        $user->notify(new EmailVerificationCodeNotification($code));
    }
}
