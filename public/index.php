<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Rdb\ContainerFactory;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

session_start();

$app = AppFactory::createFromContainer(ContainerFactory::get());

$app->add(TwigMiddleware::createFromContainer($app));

$app->get(
	'/',
	fn (Request $request, Response $response, array $args)
		=> $this->get('view')->render($response, 'home.twig', ['name' => 'Noé', 'db' => $this->get('db')])
)->setName('home');

$app->get(
	'/reviewables',
	function (Request $request, Response $response, array $args)
	{
		return $this->get('view')->render(
			$response,
			'reviewables.twig',
			[
				'reviewables' => [],
				'flash' => $this->get('flash')->getMessages(),
			]
		);
	}
)->setName('reviewables');

$app->post(
	'/reviewables/new',
	function (Request $request, Response $response, array $args) use ($app)
	{
		$this->get('flash')->addMessage('info', 'Created !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
		);
	}
);

$app->get(
	'/reviewables/new',
	fn (Request $request, Response $response, array $args)
		=> $this->get('view')->render($response, 'reviewables.new.twig')
)->setName('reviewables.new');

$app->get(
	'/reviewables/new/field',
	function (Request $request, Response $response, array $args)
	{
		if ($request->getHeaderLine('HX-Request') !== 'true')
			return $response->withStatus(403);
		return $this->get('view')->render($response, 'reviewables.new.field.twig');
	}
)->setName('reviewables.new.field');

$app->run();
