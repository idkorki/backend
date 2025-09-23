<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

final class AuthMiddleware
{
    public function __construct(private UserRepository $users) {}

    public function __invoke(Request $request, Handler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');
        if (!preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return $this->unauthorized();
        }

        $raw = base64_decode(trim($m[1]), true);
        if ($raw === false) {
            return $this->unauthorized();
        }

        $parts = explode('|', $raw);
        $id = null;
        foreach ($parts as $p) {
            if (ctype_digit($p)) { $id = (int)$p; break; }
        }
        if (!$id) {
            return $this->unauthorized();
        }

        $user = $this->users->findById($id);
        if (!$user) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute('user', $user));
    }

    private function unauthorized(): Response
    {
        $r = new SlimResponse(401);
        $r->getBody()->write(json_encode(['ok' => false, 'error' => 'unauthorized']));
        return $r->withHeader('Content-Type', 'application/json');
    }
}

