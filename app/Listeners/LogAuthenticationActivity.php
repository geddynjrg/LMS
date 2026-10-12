<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogAuthenticationActivity
{
    public function __construct(protected Request $request) {}

    public function handleLogin(Login $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        activity('auth')
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'email' => $user?->email,
                'user_id' => $user?->id,
                'guard' => $event->guard,
                'ip_address' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
                'remember' => (bool) $event->remember,
            ])
            ->log('User logged in');
    }

    public function handleFailed(Failed $event): void
    {
        $email = is_array($event->credentials)
            ? ($event->credentials['email'] ?? $event->credentials['username'] ?? null)
            : null;

        activity('auth')
            ->causedBy($event->user instanceof User ? $event->user : null)
            ->event('login_failed')
            ->withProperties([
                'email' => $email,
                'guard' => $event->guard,
                'ip_address' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
            ])
            ->log('Login failed');
    }

    public function handleLogout(Logout $event): void
    {
        /** @var User|null $user */
        $user = $event->user instanceof User ? $event->user : null;

        activity('auth')
            ->causedBy($user)
            ->event('logout')
            ->withProperties([
                'email' => $user?->email,
                'user_id' => $user?->id,
                'guard' => $event->guard,
                'ip_address' => $this->request->ip(),
                'user_agent' => (string) $this->request->userAgent(),
            ])
            ->log('User logged out');
    }
}
