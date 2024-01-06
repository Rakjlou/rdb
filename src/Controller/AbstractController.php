<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

use DI\Container;

use Slim\App;
use Slim\Views\Twig;
use Slim\Flash\Messages as FlashMessages;

abstract class AbstractController
{
	protected App $app;
	protected ContainerInterface $container;
	protected Twig $view;
	protected FlashMessages $flash;
	protected Container $repository;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		$this->app = $container->get('app');
		$this->view = $container->get('view');
		$this->flash = $container->get('flash');
		$this->repository = $container->get('repository');
	}
}
