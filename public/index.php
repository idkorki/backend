<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$builder = new ContainerBuilder();
$builder->addDefinitions(dirname(__DIR__) . '/config/dependencies.php');
$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->add(function ($request, $handler) use ($container) {
    // Origin запроса от браузера
    $reqOrigin = $request->getHeaderLine('Origin');

    $allowed = '*';
    if ($container->has('settings')) {
        $s = $container->get('settings');
        $allowed = $s['cors']['origin'] ?? '*';
    }

    $originHeader = null;
    if (is_array($allowed)) {
        if (in_array($reqOrigin, $allowed, true)) {
            $originHeader = $reqOrigin;
        }
    } else {
        $originHeader = ($allowed === '*') ? $reqOrigin : $allowed;
    }

    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $pre = new SlimResponse(200);
        if ($originHeader) {
            return $pre
                ->withHeader('Access-Control-Allow-Origin', $originHeader)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $pre;
    }

    $response = $handler->handle($request);
    if ($originHeader) {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $originHeader)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
    return $response;
});

$display = true; // по умолчанию в dev
if ($container->has('settings')) {
    $display = (bool)($container->get('settings')['displayErrorDetails'] ?? true);
}
$app->addErrorMiddleware($display, true, true);

$errorMw = $app->addErrorMiddleware($display, true, true);
$errorMw->setDefaultErrorHandler(function ($request, Throwable $e) use ($app) {
    $payload = ['error' => true, 'message' => $e->getMessage()];
    $status  = 500;
    if (method_exists($e, 'getCode') && is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600) {
        $status = $e->getCode();
    }
    $resp = $app->getResponseFactory()->createResponse($status);
    $resp->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
    return $resp->withHeader('Content-Type', 'application/json');
});

require dirname(__DIR__) . '/config/routes.php';

$app->get('/ping', function ($req, $res) {
    $res->getBody()->write(json_encode(['status' => 'ok']));
    return $res->withHeader('Content-Type', 'application/json');
});

$app->run();

