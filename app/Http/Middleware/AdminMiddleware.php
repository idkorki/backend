<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

final class AdminMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $user = $request->getAttribute('user');
        if (!is_array($user) || (($user['role'] ?? null) !== 'admin')) {
            $r = new SlimResponse(403);
            $r->getBody()->write(json_encode(['ok' => false, 'error' => 'forbidden']));
            return $r->withHeader('Content-Type', 'application/json');
        }
        return $handler->handle($request);
    }
}

