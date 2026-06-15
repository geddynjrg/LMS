<?php

namespace App\Services;

use App\Mail\UserRegisteredMail;
use App\Mail\UserPasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MailService
{
    public function sendWelcomeEmail(User $user, string $plainPassword)
    {
        Mail::to($user->email)->queue(new UserRegisteredMail($user, $plainPassword));
    }

    public function sendPasswordResetNotification(User $user, string $newPassword)
    {
        Mail::to($user->email)->queue(new UserPasswordResetMail($user, $newPassword));
    }

    /**
     * Helper to create a user and initiate the mail in one go.
    */
    public function registerAndNotify(array $data)
    {
        $password = Str::random(10); // Generate a secure 10-character password

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'status' => true, // Ensure they are active based on your middleware
        ]);

        $this->sendWelcomeEmail($user, $password);

        return $user;
    }
}
