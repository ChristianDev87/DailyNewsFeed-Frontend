<?php
declare(strict_types=1);

use App\Actions\Auth\CallbackAction;
use App\Actions\Api\BotCommandAction;
use App\Actions\Api\CategoryOrderAction;
use App\Actions\Api\CategorySaveAction;
use App\Actions\Api\FeedDeleteAction;
use App\Actions\Api\FeedSaveAction;
use App\Actions\Api\FeedTestAction;
use App\Actions\ChannelAction;
use App\Actions\DashboardAction;
use App\Actions\LoginAction;
use App\Middleware\AuthMiddleware;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../src/container.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware((bool)($_ENV['APP_DEBUG'] ?? false), true, true);

// Routen ohne Auth
$app->get('/', LoginAction::class);
$app->get('/auth/callback', CallbackAction::class);

// Routen mit Auth-Middleware
$app->group('', function (\Slim\Routing\RouteCollectorProxy $group) {
    $group->get('/dashboard', DashboardAction::class);
    $group->get('/channel/{channel_id}', ChannelAction::class);
    $group->post('/api/feed/test', FeedTestAction::class);
    $group->post('/api/feed/save', FeedSaveAction::class);
    $group->delete('/api/feed/{id}', FeedDeleteAction::class);
    $group->post('/api/category/save', CategorySaveAction::class);
    $group->post('/api/category/order', CategoryOrderAction::class);
    $group->post('/api/bot/command', BotCommandAction::class);
})->add(AuthMiddleware::class);

$app->run();
