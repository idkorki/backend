<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controller\EventsController;
use App\Controller\SubscribeController;
use App\Controller\AuthController;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

return (static function () use ($app): void {

    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/health', fn($req,$res)=>$res->withHeader('Content-Type','application/json')
            ->withBody((function(){ $s=fopen('php://temp','r+'); fwrite($s,json_encode(['status'=>'ok'])); rewind($s); return $s; })()));

        // public
        $group->get('/events',          [EventsController::class, 'getAll']);
        $group->get('/events/{id:\d+}', [EventsController::class, 'getOne']);
        $group->post('/subscribe', [SubscribeController::class, 'subscribe']);
        $group->post('/login',     [AuthController::class, 'login']);

        // protected (admin via Bearer/cookie sid)
        $group->group('', function (RouteCollectorProxy $r) {
            $r->post  ('/events',                 [EventsController::class, 'create']);
            $r->put   ('/events/{id:\d+}',        [EventsController::class, 'update']);
            $r->delete('/events/{id:\d+}',        [EventsController::class, 'delete']);
            $r->put   ('/events/{id:\d+}/status', [EventsController::class, 'updateStatus']);
        })->add(function (Request $request, RequestHandlerInterface $handler): Response {
            // Bearer ok
            $auth = $request->getHeaderLine('Authorization');
            if (preg_match('/^Bearer\s+(.+)$/i', $auth)) return $handler->handle($request);

            // cookie sid: id|email|ts
            $sid = $_COOKIE['sid'] ?? '';
            if ($sid !== '') {
                $decoded = base64_decode($sid, true) ?: '';
                $parts = explode('|', $decoded);
                if (count($parts) >= 2) {
                    [$idStr, $email] = [$parts[0], $parts[1]];
                    global $app;
                    /** @var \PDO $pdo */
                    $pdo = $app->getContainer()->get(\PDO::class);
                    $stmt = $pdo->prepare('SELECT id,email,role FROM users WHERE email=:email LIMIT 1');
                    $stmt->execute([':email'=>$email]);
                    $u = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($u && (string)$u['id']===(string)$idStr && (($u['role']??'')==='admin')) {
                        return $handler->handle($request);
                    }
                }
            }
            $resp = new SlimResponse(401);
            $resp->getBody()->write(json_encode(['error'=>true,'message'=>'unauthorized'], JSON_UNESCAPED_UNICODE));
            return $resp->withHeader('Content-Type','application/json');
        });
    });

})();

