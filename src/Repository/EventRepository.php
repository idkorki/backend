<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class EventRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query("
            SELECT id, title, description, date, startTime, endTime, status
            FROM events ORDER BY id DESC
        ")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("
            SELECT id, title, description, date, startTime, endTime, status
            FROM events WHERE id=:id
        ");
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $st = $this->pdo->prepare("
            INSERT INTO events (title, description, date, startTime, endTime, status)
            VALUES (:title, :description, :date, :startTime, :endTime, :status)
        ");
        $st->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':date'        => $data['date'] ?? null,
            ':startTime'   => $data['startTime'] ?? null,
            ':endTime'     => $data['endTime'] ?? null,
            ':status'      => $data['status'] ?? 'draft',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $st = $this->pdo->prepare("
            UPDATE events
            SET title=:title, description=:description, date=:date, startTime=:startTime, endTime=:endTime, status=:status
            WHERE id=:id
        ");
        $st->execute([
            ':id'          => $id,
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':date'        => $data['date'] ?? null,
            ':startTime'   => $data['startTime'] ?? null,
            ':endTime'     => $data['endTime'] ?? null,
            ':status'      => $data['status'] ?? 'draft',
        ]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM events WHERE id=:id");
        $st->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $st = $this->pdo->prepare("UPDATE events SET status=:status WHERE id=:id");
        $st->execute([':id' => $id, ':status' => $status]);
    }
}

