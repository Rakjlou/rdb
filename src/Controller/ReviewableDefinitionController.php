<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Rdb\Reviewable\Definition as ReviewableDefinition;
use Rdb\Reviewable\DefinitionField as ReviewableDefinitionField;
use Rdb\Reviewable\DefinitionFieldType as ReviewableDefinitionFieldType;

class ReviewableDefinitionController extends AbstractController
{
    static public function routes($app)
    {
        // Create
        $app->get('/reviewables/new', [self::class, 'create'])->setName('reviewables.new');
        $app->post('/reviewables/new', [self::class, 'createPost'])->setName('reviewables.new.post');

        // Read
        $app->get('/reviewables', [self::class, 'read'])->setName('reviewables');

        // Update
        $app->get('/reviewables/{id}/edit', [self::class, 'update'])->setName('reviewables.edit');
        $app->post('/reviewables/{id}/edit', [self::class, 'updatePost'])->setName('reviewables.edit.post');

        // Delete
        $app->delete('/reviewables/{id}', [self::class, 'delete'])->setName('reviewables.delete');

        // HTMX-only
        $app->get('/reviewables/new/field', [self::class, 'xGetField'])->setName('reviewables.field.new');
    }

    public function create(Request $request, Response $response, array $args)
    {
        return $this->view->render($response, 'reviewables.edit.twig');
    }

    public function createPost(Request $request, Response $response, array $args)
    {
        if (empty($_REQUEST['name'])
            || empty($_REQUEST['fieldName']) || !is_array($_REQUEST['fieldName'])
            || empty($_REQUEST['fieldType']) || !is_array($_REQUEST['fieldType'])
            || count($_REQUEST['fieldType']) !== count($_REQUEST['fieldName'])
        ) {
            $this->flash->addMessageNow('error', 'Bad request');
            return $this->view->render($response, 'reviewables.edit.twig')->withStatus(400);
        }

        $fieldsCount = count($_REQUEST['fieldType']);
        $repository = $this->repository->get('reviewableDefinition');
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

            $this->flash->addMessageNow('error', $e->getMessage());
            return $this->view->render(
                $response,
                'reviewables.edit.twig',
                [
                    'reviewable' => $definition
                ]
            )->withStatus($status);
        }


        $this->flash->addMessage('info', 'Created !');
        return $response->withStatus(303)->withHeader(
            'Location',
            $this->app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
        );
    }

    public function read(Request $request, Response $response, array $args)
    {
        return $this->view->render(
            $response,
            'reviewables.twig',
            [
                'reviewables' => $this->repository->get('reviewableDefinition')->findAll(),
            ]
        );
    }

    public function update(Request $request, Response $response, array $args)
    {
        $reviewable = $this->repository->get('reviewableDefinition')->findById(intval($args['id']));

		if (!$reviewable) {
			$this->flash->addMessage('error', 'Reviewable not found!');
			return $response->withStatus(404)->withHeader(
				'Location',
				$this->app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
			);
		}

		return $this->view->render($response, 'reviewables.edit.twig',
			[
				'reviewable' => $reviewable
			]
		);
    }

    public function updatePost(Request $request, Response $response, array $args)
    {
        $toNormalize = ['fieldName', 'fieldType', 'fieldNameUpdate', 'fieldTypeUpdate'];

		foreach ($toNormalize as $key)
			$_REQUEST[$key] = $_REQUEST[$key] ?? [];

		if (empty($_REQUEST['name']) || intval($args['id']) <= 0
			|| count($_REQUEST['fieldType']) !== count($_REQUEST['fieldName'])
			|| count($_REQUEST['fieldTypeUpdate']) !== count($_REQUEST['fieldNameUpdate'])
		) {
			$this->flash->addMessageNow('error', 'Bad request');
			return $this->view->render($response, 'reviewables.edit.twig')->withStatus(400);
		}

		$fieldsCount = count($_REQUEST['fieldType']);
		$fieldsUpdateCount = count($_REQUEST['fieldType']);
		$repository = $this->repository->get('reviewableDefinition');
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

			$this->flash->addMessageNow('error', $e->getMessage());
			return $this->view->render(
				$response,
				'reviewables.edit.twig',
				[
					'reviewable' => $definition
				]
			)->withStatus($status);
		}


		$this->flash->addMessage('info', 'Edited !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
		);
    }

    public function delete(Request $request, Response $response, array $args)
    {
        $this->repository->get('reviewableDefinition')->delete(intval($args['id']));
		$this->flash->addMessage('info', 'Reviewable deleted!');
		return $response->withStatus(303)->withHeader(
            'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('reviewables')
		);
    }

    public function xGetField(Request $request, Response $response, array $args)
    {
        if ($request->getHeaderLine('HX-Request') !== 'true')
            return $response->withStatus(403);
        return $this->view->render($response, 'reviewables.new.field.twig');
    }
}