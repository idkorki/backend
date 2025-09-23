<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

final class EventRepository
{
    public function __construct(private PDO $pdo) {}

    private function hydrate(?array $row): ?array
    {
        if (!$row) return null;
        if (array_key_exists('stages', $row) && $row['stages'] !== null && $row['stages'] !== '') {
            $decoded = json_decode((string)$row['stages'], true);
            $row['stages'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['stages'] = [];
        }
        return $row;
    }

    private function hydrateAll(array $rows): array
    {
        foreach ($rows as &$r) {
            $r = $this->hydrate($r);
        }
        return $rows;
    }

    public function all(): array
    {
        $st = $this->pdo->query('SELECT * FROM events ORDER BY id DESC');
        $rows = $st->fetchAll();
        return $this->hydrateAll($rows);
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch();
        return $this->hydrate($row);
    }

    public function create(array $data): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO events (title, description, date, startTime, endTime, status, stages)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            (string)($data['title'] ?? ''),
            $data['description'] ?? null,
            $data['date'] ?? null,
            $data['startTime'] ?? null,
            $data['endTime'] ?? null,
            $data['status'] ?? 'draft',
            isset($data['stages']) ? json_encode($data['stages'], JSON_UNESCAPED_UNICODE) : json_encode([]),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $st = $this->pdo->prepare(
            'UPDATE events
             SET title = ?, description = ?, date = ?, startTime = ?, endTime = ?, stages = ?
             WHERE id = ?'
        );
        return $st->execute([
            (string)($data['title'] ?? ''),
            $data['description'] ?? null,
            $data['date'] ?? null,
            $data['startTime'] ?? null,
            $data['endTime'] ?? null,
            isset($data['stages']) ? json_encode($data['stages'], JSON_UNESCAPED_UNICODE) : json_encode([]),
            $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM events WHERE id = ?');
        return $st->execute([$id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $st = $this->pdo->prepare('UPDATE events SET status = ? WHERE id = ?');
        return $st->execute([$status, $id]);
    }
}
