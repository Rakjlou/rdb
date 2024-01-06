<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Rdb\Definition\Definition as Definition;
use Rdb\Definition\Field as DefinitionField;
use Rdb\Definition\FieldType as DefinitionFieldType;

class DefinitionController extends AbstractController
{
	static public function routes($app)
	{
		// Create
		$app->get('/definitions/new', [self::class, 'create'])->setName('definitions.new');
		$app->post('/definitions/new', [self::class, 'createPost'])->setName('definitions.new.post');

		// Read
		$app->get('/definitions', [self::class, 'read'])->setName('definitions');

		// Update
		$app->get('/definitions/{id}/edit', [self::class, 'update'])->setName('definitions.edit');
		$app->post('/definitions/{id}/edit', [self::class, 'updatePost'])->setName('definitions.edit.post');

		// Delete
		$app->delete('/definitions/{id}', [self::class, 'delete'])->setName('definitions.delete');

		// HTMX-only
		$app->get('/definitions/new/field', [self::class, 'xGetField'])->setName('definitions.field.new');
	}

	public function create(Request $request, Response $response, array $args)
	{
		return $this->view->render($response, 'definitions/edit.twig');
	}

	public function createPost(Request $request, Response $response, array $args)
	{
		return $this->processCreateUpdatePost($request, $response, $args);
	}

	public function read(Request $request, Response $response, array $args)
	{
		return $this->view->render(
			$response,
			'definitions/index.twig',
			[
				'definitions' => $this->repository->get('definition')->findAll(),
			]
		);
	}

	public function update(Request $request, Response $response, array $args)
	{
		$definition = $this->repository->get('definition')->findById(intval($args['id']));

		if (!$definition) {
			$this->flash->addMessage('error', 'Definition not found!');
			return $response->withStatus(404)->withHeader(
				'Location',
				$this->app->getRouteCollector()->getRouteParser()->urlFor('definitions')
			);
		}

		return $this->view->render($response, 'definitions/edit.twig',
			[
				'definition' => $definition
			]
		);
	}

	public function updatePost(Request $request, Response $response, array $args)
	{
		return $this->processCreateUpdatePost($request, $response, $args, true);
	}

	public function delete(Request $request, Response $response, array $args)
	{
		$this->repository->get('definition')->delete(intval($args['id']));
		$this->flash->addMessage('info', 'Definition deleted!');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('definitions')
		);
	}

	public function xGetField(Request $request, Response $response, array $args)
	{
		if ($request->getHeaderLine('HX-Request') !== 'true')
			return $response->withStatus(403);
		return $this->view->render($response, 'definitions/new.field.twig');
	}

	private function processCreateUpdatePost(Request $request, Response $response, array $args, bool $isUpdate = false)
	{
		$toNormalize = ['fieldName', 'fieldType', 'fieldNameUpdate', 'fieldTypeUpdate'];

		foreach ($toNormalize as $key)
			$_REQUEST[$key] = $_REQUEST[$key] ?? [];

		if (empty($_REQUEST['name'])
			|| ($isUpdate && intval($args['id']) <= 0)
			|| count($_REQUEST['fieldType']) !== count($_REQUEST['fieldName'])
			|| count($_REQUEST['fieldTypeUpdate']) !== count($_REQUEST['fieldNameUpdate'])
		) {
			$this->flash->addMessageNow('error', 'Bad request');
			return $this->view->render($response, 'definitions/edit.twig')->withStatus(400);
		}

		$repository = $this->repository->get('definition');
		$definition = new Definition(id: $isUpdate ? intval($args['id']) : null);

		$definition->name($_REQUEST['name']);

		foreach (array_keys($_REQUEST['fieldNameUpdate']) as $key)
		{
			$definition->addField(
				new DefinitionField(
					id: intval($key),
					name: $_REQUEST['fieldNameUpdate'][$key],
					type: DefinitionFieldType::from($_REQUEST['fieldTypeUpdate'][$key])
				)
			);
		}

		foreach (array_keys($_REQUEST['fieldName']) as $key)
		{
			$definition->addField(
				new DefinitionField(
					name: $_REQUEST['fieldName'][$key],
					type: DefinitionFieldType::from($_REQUEST['fieldType'][$key])
				)
			);
		}

		try
		{
			if ($isUpdate)
				$repository->update($definition);
			else
				$repository->save($definition);
		}
		catch (\PDOException $e)
		{
			$status = 400;

			if (in_array($e->getCode(), ['23000', '28000']))
				$status = 409;

			$this->flash->addMessageNow('error', $e->getMessage());
			return $this->view->render(
				$response,
				'definitions/edit.twig',
				[
					'definition' => $definition
				]
			)->withStatus($status);
		}

		$this->flash->addMessage('info', $isUpdate ? 'Edited !' : 'Created !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('definitions')
		);
	}
}
