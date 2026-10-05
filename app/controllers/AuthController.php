<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Models\User;

final class AuthController extends Controller
{
    /** Max login attempts per IP+email window. */
    private const MAX_ATTEMPTS = 5;
    /** Lock-out window in seconds (5 minutes). */
    private const DECAY_SECONDS = 300;

    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }
        $this->render('auth/login', [
            'title' => 'Sign in',
            'old' => $this->old(),
        ], 'layouts/guest');
    }

    public function login(): void
    {
        Auth::requireCsrf();

        $email = $this->input('email');
        $password = $this->input('password');

        // Throttle repeated failed attempts per IP + email to blunt brute force.
        $limitKey = 'login|' . $this->clientIp() . '|' . strtolower($email);
        if (!RateLimiter::check($limitKey, self::MAX_ATTEMPTS, self::DECAY_SECONDS)) {
            $minutes = max(1, (int) ceil(RateLimiter::retryAfter($limitKey) / 60));
            $this->withOld();
            Flash::error("Too many failed attempts. Please try again in {$minutes} minute(s).");
            $this->back(url('/login'));
        }

        if ($email === '' || $password === '') {
            $this->withOld();
            Flash::error('Please enter your email and password.');
            $this->back(url('/login'));
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, (string) $user['password_hash'])) {
            $this->withOld();
            Flash::error('Invalid email or password.');
            $this->back(url('/login'));
        }

        // Successful credentials: clear the throttle counter.
        RateLimiter::clear($limitKey);

        Auth::login($user);
        $this->back(url('/dashboard'));
    }

    public function logout(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (Csrf::verify(is_string($token) ? $token : null)) {
            Auth::logout();
        }
        $this->back(url('/login'));
    }

    private function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
