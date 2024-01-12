<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

use Rdb\ContainerFactory;
use Rdb\Middleware\RoutingMiddleware;

session_start();

$app = AppFactory::createFromContainer(ContainerFactory::get());
$app->getContainer()->set('app', $app);

$app->add(TwigMiddleware::createFromContainer($app));
$app->add(new RoutingMiddleware($app));

$app->run();
