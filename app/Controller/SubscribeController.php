<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SubscribeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SubscribeController
{
    public function __construct(private SubscribeService $service) {}

    private function json(Response $res, mixed $data, int $code = 200): Response
    {
        $res->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $res->withStatus($code)->withHeader('Content-Type', 'application/json');
    }

    private function payload(Request $req): array
    {
        $ct = $req->getHeaderLine('Content-Type');
        if (str_contains($ct, 'application/json')) {
            $data = json_decode((string)$req->getBody(), true);
            return is_array($data) ? $data : [];
        }
        return (array)($req->getParsedBody() ?? []);
    }

    public function subscribe(Request $req, Response $res): Response
    {
        $d = $this->payload($req);
        $email = (string)($d['email'] ?? '');
        $eventId = isset($d['eventId']) ? (int)$d['eventId'] : null;

        if ($email === '') {
            return $this->json($res, ['ok' => false, 'error' => 'email_required'], 422);
        }

        $this->service->subscribe($email, $eventId);
        return $this->json($res, ['ok' => true, 'email' => $email, 'eventId' => $eventId]);
    }
}

