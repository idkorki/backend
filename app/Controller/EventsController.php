<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\StageRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class EventsController
{
    public function __construct(
        private EventRepository $events,
        private StageRepository $stages
    ) {}

    private function json(Response $res, array $payload, int $code = 200): Response
    {
        $res->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($code);
    }

    /** GET /api/events */
    public function getAll(Request $req, Response $res): Response
    {
        $rows = $this->events->all();
        $items = [];
        foreach ($rows as $e) {
            $e['stages'] = $this->stages->forEvent((int)$e['id']);
            $items[] = $e;
        }
        return $this->json($res, ['items' => $items]);
    }

    /** GET /api/events/{id} */
    public function getOne(Request $req, Response $res, array $args): Response
    {
        $id = (int)$args['id'];
        $e  = $this->events->find($id);
        if (!$e) {
            return $this->json($res, ['ok' => false, 'error' => 'not_found'], 404);
        }
        $e['stages'] = $this->stages->forEvent($id);
        return $this->json($res, $e);
    }

    /** POST /api/events (админ) */
    public function create(Request $req, Response $res): Response
    {
        $data = (array)$req->getParsedBody();
	$title = trim((string)($data['title'] ?? ''));
	$date  = trim((string)($data['date']  ?? ''));

	if ($title === '' || ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))) {
    		return $this->json($res, [
        	'error'   => 'validation',
        	'message' => 'title обязателен; date должен быть в формате YYYY-MM-DD'
   	 ], 422);
	}
        $id   = $this->events->create($data);

        if (!empty($data['stages']) && is_array($data['stages'])) {
            foreach ($data['stages'] as $s) {
                $this->stages->create($id, [
                    'title'       => (string)($s['title'] ?? ''),
                    'description' => $s['description'] ?? null,
                    'startTime'   => $s['startTime'] ?? null,
                    'endTime'     => $s['endTime'] ?? null,
                ]);
            }
        }

        return $this->json($res, ['id' => $id], 201);
    }

     public function update(Request $req, Response $res, array $args): Response
    {
        $id   = (int)$args['id'];
        $data = (array)$req->getParsedBody();
	if (array_key_exists('title', $data) || array_key_exists('date', $data)) {
 	   $title = isset($data['title']) ? trim((string)$data['title']) : 'ok';
 	   $date  = isset($data['date'])  ? trim((string)$data['date'])  : '';
 	   if ($title === '' || ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))) {
 	       return $this->json($res, [
 	           'error'   => 'validation',
 	           'message' => 'title обязателен; date должен быть YYYY-MM-DD'
 	       ], 422);
    }
}
        $ok = $this->events->update($id, $data);

        if (array_key_exists('stages', $data) && is_array($data['stages'])) {
            $this->stages->replaceForEvent($id, $data['stages']);
        }

        return $this->json($res, ['ok' => (bool)$ok]);
    }

    /** DELETE /api/events/{id} (админ) */
    public function delete(Request $req, Response $res, array $args): Response
    {
        $id = (int)$args['id'];
        $ok = $this->events->delete($id);
        return $this->json($res, ['ok' => (bool)$ok]);
    }

    /** PUT /api/events/{id}/status (админ) */
    public function updateStatus(Request $req, Response $res, array $args): Response
    {
        $id   = (int)$args['id'];
        $data = (array)$req->getParsedBody();
        $status = (string)($data['status'] ?? 'draft');

        $ok = $this->events->updateStatus($id, $status);
        return $this->json($res, ['ok' => (bool)$ok]);
    }
}

