<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Models\User;

final class AuthController extends Controller
{
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
}