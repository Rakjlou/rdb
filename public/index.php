<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Rdb\ContainerFactory;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

session_start();

$app = AppFactory::createFromContainer(ContainerFactory::get());
$app->getContainer()->set('app', $app);

$app->add(TwigMiddleware::createFromContainer($app));

Rdb\Controller\HomeController::routes($app);
Rdb\Controller\DefinitionController::routes($app);

$app->run();
