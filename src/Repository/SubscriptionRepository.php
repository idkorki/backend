<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SubscriptionRepository
{
    public function __construct(private PDO $pdo) {}

    public function create(string $email, int $eventId): void
    {
        $st = $this->pdo->prepare("INSERT INTO subscriptions (email, eventId) VALUES (:email, :eventId)");
        $st->execute([':email' => $email, ':eventId' => $eventId]);
    }
}

