<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Rdb\Grading\Scale as Scale;
use Rdb\Grading\Criteria as Criteria;

class GradingController extends AbstractController
{
	public function route(): ControllerInterface
	{
		$app = $this->app;

		// Create
		$app->get('/grading/new', [$this, 'create'])->setName('grading.new');
		$app->post('/grading/new', [$this, 'createPost'])->setName('grading.new.post');

		// Read
		$app->get('/grading', [$this, 'read'])->setName('grading');

		// Update
		$app->get('/grading/{id}/edit', [$this, 'update'])->setName('grading.edit');
		$app->post('/grading/{id}/edit', [$this, 'updatePost'])->setName('grading.edit.post');

		// Delete
		$app->delete('/grading/{id}', [$this, 'delete'])->setName('grading.delete');

		// HTMX-only
		$app->get('/grading/new/criteria', [$this, 'xGetCriteria'])->setName('grading.criteria.new');

		return $this;
	}

	public function create(Request $request, Response $response, array $args)
	{
		return $this->view->render($response, 'grading/edit.twig');
	}

	public function createPost(Request $request, Response $response, array $args)
	{
		return $this->processCreateUpdatePost($request, $response, $args);
	}

	public function read(Request $request, Response $response, array $args)
	{
		return $this->view->render(
			$response,
			'grading/index.twig',
			[
				'scales' => $this->repository->get('grading')->findAll(),
			]
		);
	}

	public function update(Request $request, Response $response, array $args)
	{
		$scale = $this->repository->get('grading')->findById(intval($args['id']));

		if (!$scale) {
			$this->flash->addMessage('error', 'Scale not found!');
			return $response->withStatus(404)->withHeader(
				'Location',
				$this->app->getRouteCollector()->getRouteParser()->urlFor('grading')
			);
		}

		return $this->view->render($response, 'grading/edit.twig',
			[
				'scale' => $scale
			]
		);
	}

	public function updatePost(Request $request, Response $response, array $args)
	{
		return $this->processCreateUpdatePost($request, $response, $args, true);
	}

	public function delete(Request $request, Response $response, array $args)
	{
		$this->repository->get('grading')->delete(intval($args['id']));
		$this->flash->addMessage('success', 'Scale deleted!');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('grading')
		);
	}

	public function xGetCriteria(Request $request, Response $response, array $args)
	{
		if ($request->getHeaderLine('HX-Request') !== 'true')
			return $response->withStatus(403);
		return $this->view->render($response, 'grading/new.criteria.twig');
	}

	private function processCreateUpdatePost(Request $request, Response $response, array $args, bool $isUpdate = false)
	{
		$formData = $request->getParsedBody();

		if (!isset($formData['name']) || empty($formData['name'])
			|| (isset($formData['criteria']) && (empty($formData['criteria']) || !is_array($formData['criteria'])))
			|| (isset($formData['updateCriteria']) && (empty($formData['updateCriteria']) || !is_array($formData['updateCriteria'])))
		)
		{
			$this->flash->addMessageNow('error', 'Bad request1');
			return $this->view->render($response, 'grading/edit.twig')->withStatus(400);
		}

		$scale = new Scale(
			id: $isUpdate ? intval($args['id']) : null,
			name: trim($formData['name'])
		);

		foreach	(['updateCriteria', 'criteria'] as $criteriaList)
		{
			if (!isset($formData[$criteriaList]))
				continue;

			foreach ($formData[$criteriaList] as $criteriaId => $criteria)
			{
				if (!isset($criteria['name']) || !isset($criteria['min']) || !isset($criteria['max']))
				{
					$this->flash->addMessageNow('error', 'Bad request2');
					return $this->view->render($response, 'grading/edit.twig')->withStatus(400);
				}

				$scale->addCriteria(new Criteria(
					id: $criteriaList === 'updateCriteria' ? $criteriaId : null,
					name: empty(trim($criteria['name'])) || count($formData[$criteriaList]) === 1 ? null : trim($criteria['name']),
					min: intval($criteria['min']),
					max: intval($criteria['max'])
				));
			}
		}

		try
		{
			$repository = $this->repository->get('grading');

			if ($isUpdate)
				$repository->update($scale);
			else
				$repository->save($scale);
		}
		catch (\PDOException $e)
		{
			$status = 400;

			if (in_array($e->getCode(), ['23000', '28000']))
				$status = 409;

			$this->flash->addMessageNow('error', $e->getMessage());
			return $this->view->render(
				$response,
				'grading/edit.twig',
				[
					'scale' => $scale
				]
			)->withStatus($status);
		}

		$this->flash->addMessage('success', $isUpdate ? 'Edited !' : 'Created !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->app->getRouteCollector()->getRouteParser()->urlFor('grading')
		);
	}

}
