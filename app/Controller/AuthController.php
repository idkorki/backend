<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(private AuthService $auth) {}

    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        // принимаем оба варианта имён полей, чтобы не упираться
        $email = (string) ($body['email'] ?? $body['login'] ?? '');
        $pass  = (string) ($body['password'] ?? $body['pass'] ?? '');

        $user = $this->auth->login($email, $pass);

        // ставим сессионную куку для локалки
        // SameSite=Lax ок, так как фронт и бэк на одном site (localhost)
        $sessionId = base64_encode($user['id'] . '|' . $user['email'] . '|' . time());
        setcookie('sid', $sessionId, [
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => false, // в проде под HTTPS сделай true
        ]);

        $payload = json_encode($user, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload ?? '{}');
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response): Response
    {
        // гасим куку
        setcookie('sid', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => false,
        ]);

        $response->getBody()->write(json_encode(['ok' => true], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

