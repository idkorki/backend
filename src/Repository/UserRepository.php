<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT id, email, password, role FROM users WHERE email=:email LIMIT 1');
        $st->execute([':email' => $email]);
        $row = $st->fetch();
        return $row ?: null;
    }
}

