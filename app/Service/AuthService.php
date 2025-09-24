<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use DomainException;

final class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function login(string $email, string $password): array
    {
        $email = trim($email);
        if ($email === '' || $password === '') {
            throw new DomainException('email_and_password_required', 422);
        }

        $user = $this->users->findByEmail($email);

        if (
            !$user
            || !isset($user['password_hash'])
            || !password_verify($password, (string)$user['password_hash'])
        ) {
            throw new DomainException('invalid_credentials', 401);
        }

        unset($user['password_hash']);

        $user['token'] = base64_encode($user['email'] . '|' . $user['id'] . '|' . time());

        return [
            'id'    => (int)$user['id'],
            'email' => (string)$user['email'],
            'role'  => $user['role'] ?? null,
            'token' => (string)$user['token'],
        ];
    }

    public function register(string $email, string $password, string $role = 'user'): array
    {
        $email = trim($email);
        if ($email === '' || $password === '') {
            throw new \DomainException('email_and_password_required', 422);
        }

        if ($this->users->findByEmail($email)) {
            throw new \DomainException('email_taken', 409);
        }

        // сохраняем bcrypt-хеш
        $id = $this->users->create($email, password_hash($password, PASSWORD_BCRYPT), $role);

        return [
            'id'    => $id,
            'email' => $email,
            'role'  => $role,
        ];
    }
}

