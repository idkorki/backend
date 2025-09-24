<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $sql = <<<SQL
SELECT 
    id,
    email,
    role,
    password_hash         -- <<< ВАЖНО: вернуть именно это поле
FROM users
WHERE email = :email
LIMIT 1
SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(string $email, string $passwordHash, string $role = 'user'): int
    {
        $sql = 'INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':role'          => $role,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}

