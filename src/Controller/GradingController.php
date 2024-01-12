<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Rdb\Grading\Scale as Scale;
use Rdb\Grading\Criteria as Criteria;
use Rdb\Middleware\HtmxOnlyMiddleware;

class GradingController extends CrudController
{
	public function route(): ControllerInterface
	{
		parent::route();

		$this->app->get('/grading/new/criteria', [$this, 'xGetCriteria'])
			->setName('grading.criteria.new')
			->add(HtmxOnlyMiddleware::class);
		return $this;
	}

	public function prefix(): string
	{
		return 'grading';
	}

	public function create(Request $request, Response $response, array $args): Response
	{
		return $this->render($response, 'grading/edit.twig');
	}

	public function createPost(Request $request, Response $response, array $args): Response
	{
		return $this->processCreateUpdatePost($request, $response, $args);
	}

	public function read(Request $request, Response $response, array $args): Response
	{
		return $this->render(
			$response,
			'grading/index.twig',
			[
				'scales' => $this->repository('grading')->findAll(),
			]
		);
	}

	public function update(Request $request, Response $response, array $args): Response
	{
		$scale = $this->repository('grading')->findById(intval($args['id']));

		if (!$scale) {
			$this->flashError('Scale not found!');
			return $response->withStatus(404)->withHeader(
				'Location',
				$this->urlFor('grading')
			);
		}

		return $this->render($response, 'grading/edit.twig',
			[
				'scale' => $scale
			]
		);
	}

	public function updatePost(Request $request, Response $response, array $args): Response
	{
		return $this->processCreateUpdatePost($request, $response, $args, true);
	}

	public function delete(Request $request, Response $response, array $args): Response
	{
		$this->repository('grading')->delete(intval($args['id']));
		$this->flashSuccess('Scale deleted!');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->urlFor('grading')
		);
	}

	public function xGetCriteria(Request $request, Response $response, array $args)
	{
		return $this->render($response, 'grading/new.criteria.twig');
	}

	private function processCreateUpdatePost(Request $request, Response $response, array $args, bool $isUpdate = false)
	{
		$formData = $request->getParsedBody();

		if (!isset($formData['name']) || empty($formData['name'])
			|| (isset($formData['criteria']) && (empty($formData['criteria']) || !is_array($formData['criteria'])))
			|| (isset($formData['updateCriteria']) && (empty($formData['updateCriteria']) || !is_array($formData['updateCriteria'])))
		)
		{
			$this->flashErrorNow('Bad request');
			return $this->render($response, 'grading/edit.twig')->withStatus(400);
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
					$this->flashErrorNow('Bad request');
					return $this->render($response, 'grading/edit.twig')->withStatus(400);
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
			$repository = $this->repository('grading');

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

			$this->flashErrorNow($e->getMessage());
			return $this->render(
				$response,
				'grading/edit.twig',
				[
					'scale' => $scale
				]
			)->withStatus($status);
		}

		$this->flashSuccess($isUpdate ? 'Edited !' : 'Created !');
		return $response->withStatus(303)->withHeader(
			'Location',
			$this->urlFor('grading')
		);
	}

}
