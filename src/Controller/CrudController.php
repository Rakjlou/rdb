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
		$prefix = $this->prefix();

		// Create
		$app->get("/$prefix/new", [$this, 'create'])->setName("$prefix.new");
		$app->post("/$prefix/new", [$this, 'createPost'])->setName("$prefix.new.post");

		// Read
		$app->get("/$prefix", [$this, 'read'])->setName("$prefix");

		// Update
		$app->get("/$prefix/{id}/edit", [$this, 'update'])->setName("$prefix.edit");
		$app->post("/$prefix/{id}/edit", [$this, 'updatePost'])->setName("$prefix.edit.post");

		// Delete
		$app->delete("/$prefix/{id}", [$this, 'delete'])->setName("$prefix.delete");

		return $this;
	}
}
