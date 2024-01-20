<?php
namespace Rdb\Factory;

use Slim\Factory\AppFactory as SlimAppFactory;
use Slim\Views\TwigMiddleware;
use Slim\App;

use DI\Container;

use Rdb\ContainerFactory;
use Rdb\Middleware\RoutingMiddleware;
use Rdb\Middleware\AppAsAttributeMiddleware;
use Rdb\Container\AppContainer;

class AppFactory
{
	public static function create(): App
	{
		$container = new Container();

		$app = SlimAppFactory::createFromContainer($container);
		AppContainer::setup($app);

		$app->add(TwigMiddleware::createFromContainer($app));
		$app->add(new RoutingMiddleware());
		$app->add(new AppAsAttributeMiddleware($app));

		return $app;
	}

	protected static function setupContainer(Container $container): void
	{
		ContainerFactory::setContainer($container);
	}
}