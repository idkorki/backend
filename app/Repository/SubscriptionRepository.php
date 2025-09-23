<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SubscriptionRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(string $email, ?int $eventId): void
    {
        $st = $this->pdo->prepare('INSERT INTO subscriptions (email, eventId) VALUES (?, ?)');
        $st->execute([$email, $eventId]);
    }

    public function exists(string $email, ?int $eventId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1 FROM subscriptions WHERE email = ? AND IFNULL(eventId,0) = IFNULL(?,0) LIMIT 1'
        );
        $st->execute([$email, $eventId]);
        return (bool)$st->fetchColumn();
    }
}

