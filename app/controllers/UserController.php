<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Models\User;

final class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $search = $this->query('q');

        $this->render('users/index', [
            'title' => 'Users',
            'users' => User::all($search !== '' ? $search : null),
            'search' => $search,
            'old' => $this->old(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Auth::requireCsrf();

        $name = $this->input('name');
        $email = strtolower($this->input('email'));
        $password = $this->input('password');
        $role = strtoupper($this->input('role'));

        if ($name === '' || $email === '' || $password === '') {
            $this->withOld();
            Flash::error('Name, email and password are required.');
            $this->back(url('/users'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->withOld();
            Flash::error('Please enter a valid email address.');
            $this->back(url('/users'));
        }
        if (strlen($password) < 6) {
            $this->withOld();
            Flash::error('Password must be at least 6 characters.');
            $this->back(url('/users'));
        }
        if (!in_array($role, ['ADMIN', 'OPERATOR'], true)) {
            $role = 'OPERATOR';
        }
        if (User::emailExists($email)) {
            $this->withOld();
            Flash::error('A user with this email already exists.');
            $this->back(url('/users'));
        }

        User::create($name, $email, $password, $role);
        Flash::success('User added successfully.');
        $this->back(url('/users'));
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Auth::requireCsrf();

        $user = User::find($id);
        if (!$user) {
            Flash::error('User not found.');
            $this->back(url('/users'));
        }

        $name = $this->input('name');
        $email = strtolower($this->input('email'));
        $password = $this->input('password');
        $role = strtoupper($this->input('role'));

        if ($name === '' || $email === '') {
            Flash::error('Name and email are required.');
            $this->back(url('/users'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::error('Please enter a valid email address.');
            $this->back(url('/users'));
        }
        if ($password !== '' && strlen($password) < 6) {
            Flash::error('Password must be at least 6 characters.');
            $this->back(url('/users'));
        }
        if (User::emailExists($email, $id)) {
            Flash::error('A user with this email already exists.');
            $this->back(url('/users'));
        }
        if (!in_array($role, ['ADMIN', 'OPERATOR'], true)) {
            $role = 'OPERATOR';
        }
        if (Auth::id() === $id && $role !== 'ADMIN') {
            Flash::error('You cannot remove your own admin role.');
            $this->back(url('/users'));
        }

        User::update($id, $name, $email, $role, $password !== '' ? $password : null);
        Flash::success('User updated successfully.');
        $this->back(url('/users'));
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Auth::requireCsrf();

        if (Auth::id() === $id) {
            Flash::error('You cannot delete your own account.');
            $this->back(url('/users'));
        }

        User::delete($id);
        Flash::success('User deleted.');
        $this->back(url('/users'));
    }
}