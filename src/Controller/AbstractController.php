<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

use DI\Container;

use Slim\App;
use Slim\Views\Twig;
use Slim\Flash\Messages as FlashMessages;
use Slim\Routing\RouteParser;

abstract class AbstractController implements ControllerInterface
{
	protected App $app;
	protected ContainerInterface $container;
	protected Twig $view;
	protected FlashMessages $flash;
	protected Container $repository;
	protected RouteParser $routeParser;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		$this->app = $container->get('app');
		$this->view = $container->get('view');
		$this->flash = $container->get('flash');
		$this->repository = $container->get('repository');
		$this->routeParser = $this->app->getRouteCollector()->getRouteParser();
	}

	public function render(...$args): Response
	{
		return $this->view->render(...$args);
	}

	public function urlFor(...$args): string
	{
		return $this->routeParser->urlFor(...$args);
	}

	public function repository(string $name): object
	{
		return $this->repository->get($name);
	}

	public function flashInfo(...$args): void
	{
		$this->flash('info', ...$args);
	}
	public function flashSuccess(...$args): void
	{
		$this->flash('success', ...$args);
	}
	public function flashError(...$args): void
	{
		$this->flash('error', ...$args);
	}
	public function flashWarning(...$args): void
	{
		$this->flash('warning', ...$args);
	}

	public function flash(string $key, string $message, bool $now = false): void
	{
		if ($now)
			$this->flash->addMessageNow($key, $message);
		else
			$this->flash->addMessage($key, $message);

	}
}
