<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controller\EventsController;
use App\Controller\SubscribeController;
use App\Controller\AuthController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AdminMiddleware;

return (static function () use ($app): void {
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/health', function ($req, $res) {
            $res->getBody()->write(json_encode(['status' => 'ok']));
            return $res->withHeader('Content-Type', 'application/json');
        });

        $group->get('/events',          [EventsController::class, 'getAll']);
        $group->get('/events/{id:\d+}', [EventsController::class, 'getOne']);

        $group->post('/subscribe', [SubscribeController::class, 'subscribe']);
        $group->post('/login',     [AuthController::class, 'login']);

        $group->group('', function (RouteCollectorProxy $r) {
            $r->post  ('/events',                 [EventsController::class, 'create']);
            $r->put   ('/events/{id:\d+}',        [EventsController::class, 'update']);
            $r->delete('/events/{id:\d+}',        [EventsController::class, 'delete']);
            $r->put   ('/events/{id:\d+}/status', [EventsController::class, 'updateStatus']);
        })->add(AdminMiddleware::class)
          ->add(AuthMiddleware::class);
    });
})();


