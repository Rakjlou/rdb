<?php

require __DIR__ . '/../vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Rdb\ContainerFactory;
use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;

use Rdb\Reviewable\Definition as ReviewableDefinition;
use Rdb\Reviewable\DefinitionField as ReviewableDefinitionField;
use Rdb\Reviewable\DefinitionFieldType as ReviewableDefinitionFieldType;

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
				'reviewables' => $this->get('repository')->get('reviewableDefinition')->findAll(),
			]
		);
	}
)->setName('reviewables');

$app->post(
	'/reviewables/new',
	function (Request $request, Response $response, array $args) use ($app)
	{
		if (empty($_REQUEST['name'])
			|| empty($_REQUEST['fieldName']) || !is_array($_REQUEST['fieldName'])
			|| empty($_REQUEST['fieldType']) || !is_array($_REQUEST['fieldType'])
			|| count($_REQUEST['fieldType']) !== count($_REQUEST['fieldName'])
		) {
			$this->get('flash')->addMessageNow('error', 'Bad request');
			return $this->get('view')->render($response, 'reviewables.edit.twig')->withStatus(400);
		}

		$fieldsCount = count($_REQUEST['fieldType']);
		$repository = $this->get('repository')->get('reviewableDefinition');
		$definition = new ReviewableDefinition;

		$definition->name($_REQUEST['name']);

		for ($i = 0; $i < $fieldsCount; $i++)
		{
			$field = new ReviewableDefinitionField;

			$field->name($_REQUEST['fieldName'][$i]);
			$field->type($_REQUEST['fieldType'][$i]);
			$definition->addField($field);
		}

		try
		{
			$repository->save($definition);
		}
		catch (\PDOException $e)
		{
			$status = 400;

			if (in_array($e->getCode(), ['23000', '28000']))
				$status = 409;

			$this->get('flash')->addMessageNow('error', $e->getMessage());
			return $this->get('view')->render(
				$response,
				'reviewables.edit.twig',
				[
					'reviewable' => $definition
				]
			)->withStatus($status);
		}


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
		=> $this->get('view')->render($response, 'reviewables.edit.twig')
)->setName('reviewables.new');

$app->post(
	'/reviewables/{id}/edit',
	function (Request $request, Response $response, array $args) use ($app)
	{
		$toNormalize = ['fieldName', 'fieldType', 'fieldNameUpdate', 'fieldTypeUpdate'];

		foreach ($toNormalize as $key)
			$_REQUEST[$key] = $_REQUEST[$key] ?? [];

		if (empty($_REQUEST['name']) || intval($args['id']) <= 0
			|| count($_REQUEST['fieldType']) !== count($_REQUEST['fieldName'])
			|| count($_REQUEST['fieldTypeUpdate']) !== count($_REQUEST['fieldNameUpdate'])
		) {
			$this->get('flash')->addMessageNow('error', 'Bad request');
			return $this->get('view')->render($response, 'reviewables.edit.twig')->withStatus(400);
		}

		$fieldsCount = count($_REQUEST['fieldType']);
		$fieldsUpdateCount = count($_REQUEST['fieldType']);
		$repository = $this->get('repository')->get('reviewableDefinition');
		$definition = new ReviewableDefinition(id: intval($args['id']));

		$definition->name($_REQUEST['name']);

		foreach (array_keys($_REQUEST['fieldNameUpdate']) as $key)
		{
			$definition->addField(
				new ReviewableDefinitionField(
					id: intval($key),
					name: $_REQUEST['fieldNameUpdate'][$key],
					type: ReviewableDefinitionFieldType::from($_REQUEST['fieldTypeUpdate'][$key])
				)
			);
		}

		foreach (array_keys($_REQUEST['fieldName']) as $key)
		{
			$definition->addField(
				new ReviewableDefinitionField(
					name: $_REQUEST['fieldName'][$key],
					type: ReviewableDefinitionFieldType::from($_REQUEST['fieldType'][$key])
				)
			);
		}

		try
		{
			$repository->update($definition);
		}
		catch (\PDOException $e)
		{
			$status = 400;

			if (in_array($e->getCode(), ['23000', '28000']))
				$status = 409;

			$this->get('flash')->addMessageNow('error', $e->getMessage());
			return $this->get('view')->render(
				$response,
				'reviewables.edit.twig',
				[
					'reviewable' => $definition
				]
			)->withStatus($status);
		}


		$this->get('flash')->addMessage('info', 'Edited !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
		);
	}
)->setName('reviewables.edit');

$app->get(
	'/reviewables/{id}/edit',
	function (Request $request, Response $response, array $args) use ($app)
	{
		$reviewable = $this->get('repository')->get('reviewableDefinition')->findById(intval($args['id']));

		if (!$reviewable) {
			$this->get('flash')->addMessage('error', 'Reviewable not found!');
			return $response->withStatus(404)->withHeader(
				'Location',
				$app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
			);
		}

		return $this->get('view')->render(
			$response,
			'reviewables.edit.twig',
			[
				'reviewable' => $reviewable
			]
		);
	}
)->setName('reviewables.edit');

$app->delete(
	'/reviewables/{id}',
	function (Request $request, Response $response, array $args) use ($app)
	{
		$this->get('repository')->get('reviewableDefinition')->delete(intval($args['id']));
		$this->get('flash')->addMessage('info', 'Reviewable deleted!');
		return $response->withStatus(303)->withHeader(
			'Location',
			$app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
		);
	}
)->setName('reviewables.delete');

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
