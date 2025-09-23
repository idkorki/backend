<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;

final class AuthController
{
    public function __construct(private AuthService $auth) {}

    public function login($req, Response $res): Response
    {
        $data = (array)$req->getParsedBody();
        $email = (string)($data['email'] ?? '');
        $password = (string)($data['password'] ?? '');

        $user = $this->auth->login($email, $password);

        $token = base64_encode($user['email'] . '|' . (int)$user['id'] . '|' . time());

        $res->getBody()->write(json_encode([
            'ok'   => true,
            'user' => [
                'id'    => (int)$user['id'],
                'email' => $user['email'],
                'role'  => $user['role'] ?? null,
                'token' => $token,
            ],
        ]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}


