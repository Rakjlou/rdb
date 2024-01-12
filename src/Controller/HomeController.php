<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends AbstractController
{
	public function route(): ControllerInterface
	{
		$app = $this->app;

		$app->get('/', [$this, 'home'])->setName('home');

		return $this;
	}

	public function home(Request $request, Response $response, array $args)
	{
		return $this->render($response, 'home.twig', ['name' => 'Noé']);
	}
}
