<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class CrudController extends AbstractController
{
	abstract public function prefix(): string;

	abstract public function create(Request $request, Response $response, array $args): Response;
	abstract public function createPost(Request $request, Response $response, array $args): Response;
	abstract public function read(Request $request, Response $response, array $args): Response;
	abstract public function update(Request $request, Response $response, array $args): Response;
	abstract public function updatePost(Request $request, Response $response, array $args): Response;
	abstract public function delete(Request $request, Response $response, array $args): Response;

	public function route(): ControllerInterface
	{
		$app = $this->app;

		// Create
		$app->get("/{$this->prefix()}/new", [$this, 'create'])->setName("{$this->prefix()}.new");
		$app->post("/{$this->prefix()}/new", [$this, 'createPost'])->setName("{$this->prefix()}.new.post");

		// Read
		$app->get("/{$this->prefix()}", [$this, 'read'])->setName("{$this->prefix()}");

		// Update
		$app->get("/{$this->prefix()}/{id}/edit", [$this, 'update'])->setName("{$this->prefix()}.edit");
		$app->post("/{$this->prefix()}/{id}/edit", [$this, 'updatePost'])->setName("{$this->prefix()}.edit.post");

		// Delete
		$app->delete("/{$this->prefix()}/{id}", [$this, 'delete'])->setName("{$this->prefix()}.delete");

		return $this;
	}
}
