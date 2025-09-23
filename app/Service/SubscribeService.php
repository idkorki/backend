<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\SubscriptionRepository;
use DomainException;

final class SubscribeService
{
    public function __construct(private SubscriptionRepository $repo) {}

    public function subscribe(string $email, int $eventId): void
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('invalid_email', 422);
        }
        if ($eventId < 0) {
            throw new DomainException('invalid_event_id', 400);
        }
        if (method_exists($this->repo, 'exists') && $this->repo->exists($email, $eventId)) {
            return;
        }
        $this->repo->create($email, $eventId ?: null);
    }
}
