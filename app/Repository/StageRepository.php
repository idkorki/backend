<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class StageRepository
{
    public function __construct(private PDO $pdo) {}

    public function forEvent(int $eventId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM stages WHERE event_id = ? ORDER BY id ASC');
        $st->execute([$eventId]);
        return $st->fetchAll() ?: [];
    }

    public function deleteByEvent(int $eventId): void
    {
        $st = $this->pdo->prepare('DELETE FROM stages WHERE event_id = ?');
        $st->execute([$eventId]);
    }

    /** Создать один этап */
    public function create(int $eventId, array $data): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO stages (event_id, title, description, startTime, endTime)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([
            $eventId,
            (string)($data['title'] ?? ''),
            $data['description'] ?? null,
            $data['startTime'] ?? null,
            $data['endTime'] ?? null,
        ]);
    }

    public function replaceForEvent(int $eventId, array $stages): void
    {
        $this->deleteByEvent($eventId);
        if (empty($stages)) {
            return;
        }

        $st = $this->pdo->prepare(
            'INSERT INTO stages (event_id, title, description, startTime, endTime)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($stages as $s) {
            $st->execute([
                $eventId,
                (string)($s['title'] ?? ''),
                $s['description'] ?? null,
                $s['startTime'] ?? null,
                $s['endTime'] ?? null,
            ]);
        }
    }
}

