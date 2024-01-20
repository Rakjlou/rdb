<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReviewController extends CrudController
{
	public function prefix(): string
	{
		return 'review';
	}

	public function route(): ControllerInterface
	{
		$app = $this->app;
		$prefix = $this->prefix();

		// Create
		$app->get("/$prefix/{slug}/new", [$this, 'create'])->setName("$prefix.new");
		$app->post("/$prefix/{slug}/new", [$this, 'createPost'])->setName("$prefix.new.post");

		// Read
		$app->get("/$prefix/{slug}", [$this, 'read'])->setName("$prefix");

		// Update
		$app->get("/$prefix/{slug}/{id}/edit", [$this, 'update'])->setName("$prefix.edit");
		$app->post("/$prefix/{slug}/{id}/edit", [$this, 'updatePost'])->setName("$prefix.edit.post");

		// Delete
		$app->delete("/$prefix/{slug}/{id}", [$this, 'delete'])->setName("$prefix.delete");

		return $this;
	}

	public function create(Request $request, Response $response, array $args): Response
	{
		$response->getBody()->write('create');
		return $response;
	}

	public function createPost(Request $request, Response $response, array $args): Response
	{
		$response->getBody()->write('createPost');
		return $response;
	}

	public function read(Request $request, Response $response, array $args): Response
	{
		$definition = $this->repository('definition')->findBySlug($args['slug']);

		if (!$definition)
			return $response->withStatus(404);

		return $this->render(
			$response,
			'review/index.twig',
			[
				'definition' => $definition,
			]
		);
	}

	public function update(Request $request, Response $response, array $args): Response
	{
		$response->getBody()->write('update');
		return $response;
	}

	public function updatePost(Request $request, Response $response, array $args): Response
	{
		$response->getBody()->write('updatePost');
		return $response;
	}

	public function delete(Request $request, Response $response, array $args): Response
	{
		$response->getBody()->write('delete');
		return $response;
	}
}