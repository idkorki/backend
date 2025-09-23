<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\EventRepository;
use DomainException;

final class EventService
{
    public function __construct(private EventRepository $repo) {}

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->repo->all();
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        if ($id <= 0) {
            throw new DomainException('invalid id', 400);
        }
        $row = $this->repo->find($id);
        if (!$row) {
            throw new DomainException('not_found', 404);
        }
        return $row;
    }

    public function create(array $data): int
    {
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            throw new DomainException('title_required', 422);
        }
        $payload = [
            'title'       => $title,
            'description' => isset($data['description']) ? (string)$data['description'] : null,
            'date'        => isset($data['date']) ? (string)$data['date'] : null,
            'startTime'   => isset($data['startTime']) ? (string)$data['startTime'] : null,
            'endTime'     => isset($data['endTime']) ? (string)$data['endTime'] : null,
            'status'      => $this->normalizeStatus((string)($data['status'] ?? 'draft')),
        ];
        return $this->repo->create($payload);
    }

    public function update(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new DomainException('invalid id', 400);
        }
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            throw new DomainException('title_required', 422);
        }
        $payload = [
            'title'       => $title,
            'description' => isset($data['description']) ? (string)$data['description'] : null,
            'date'        => isset($data['date']) ? (string)$data['date'] : null,
            'startTime'   => isset($data['startTime']) ? (string)$data['startTime'] : null,
            'endTime'     => isset($data['endTime']) ? (string)$data['endTime'] : null,
        ];
        $this->repo->update($id, $payload);
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new DomainException('invalid id', 400);
        }
        $this->repo->delete($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        if ($id <= 0) {
            throw new DomainException('invalid id', 400);
        }
        $status = $this->normalizeStatus($status);
        $this->repo->updateStatus($id, $status);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['draft', 'published', 'archived'];
        return in_array($status, $allowed, true) ? $status : 'draft';
    }
}
