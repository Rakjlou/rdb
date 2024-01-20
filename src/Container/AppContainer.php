<?php
namespace Rdb\Container;

use Slim\App;
use Slim\Views\Twig;
use Slim\Flash\Messages;

use DI\Container;

use Cocur\Slugify\Slugify;

use Rdb\Db\SQLiteDatabase;

use Rdb\Repository\DefinitionRepository;
use Rdb\Repository\GradingRepository;

use Rdb\Controller\HomeController;
use Rdb\Controller\GradingController;
use Rdb\Controller\DefinitionController;
use Rdb\Controller\ReviewController;

use Rdb\Middleware\HtmxOnlyMiddleware;

class AppContainer
{
	static public function setup(App $app)
	{
		$container = $app->getContainer();

		$container->set('app', $app);

		self::setFlash($container);
		self::setDb($container);
		self::setRepository($container);
		self::setView($container);
		self::setController($container);

		$container->set(HtmxOnlyMiddleware::class, fn() => new HtmxOnlyMiddleware($app));
	}

	static protected function setFlash(Container $container): void
	{
		$container->set('flash', fn() => new Messages());
	}

	static protected function setDb(Container $container): void
	{
		$container->set(
			'db',
			fn() => new SQLiteDatabase(implode(DIRECTORY_SEPARATOR, [dirname(dirname(__DIR__)), 'data', 'database.sqlite']))
		);
	}

	static protected function setRepository(Container $container): void
	{
		$container->set(
			'repository',
			function () use ($container)
			{
				$repositoryContainer = new Container();

				$repositoryContainer->set('definition', fn () => new DefinitionRepository($container->get('db'), $container));
				$repositoryContainer->set('grading', fn () => new GradingRepository($container->get('db'), $container));

				return $repositoryContainer;
			}
		);
	}

	static protected function setView(Container $container): void
	{
		$container->set(
			'view',
			function () use ($container)
			{
				$twig = Twig::create(
					dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'view',
					[
						'cache' => false,
						'debug' => true
					]
				);

				$twig->getEnvironment()->addGlobal('container', $container);
				$twig->getEnvironment()->addGlobal('flash', $container->get('flash'));
				$twig->getEnvironment()->addGlobal('site', [
					'lang' => 'en',
				]);
				$twig->addExtension(new \Twig\Extension\DebugExtension());

				$twig->getEnvironment()->addFilter(new \Twig\TwigFilter('slug', fn ($string) => (new Slugify())->slugify($string)));
				$twig->getEnvironment()->addFunction(new \Twig\TwigFunction('callstatic', function ($class, $method, ...$args) {
					if (!class_exists($class)) {
						throw new \Exception("Cannot call static method $method on Class $class: Invalid Class");
					}

					if (!method_exists($class, $method)) {
						throw new \Exception("Cannot call static method $method on Class $class: Invalid method");
					}

					return forward_static_call_array([$class, $method], $args);
				}));

				return $twig;
			}
		);
	}

	static protected function setController(Container $container): void
	{
		$container->set(
			'controller',
			function () use ($container)
			{
				$controllersContainer = new Container();

				$controllersContainer->set('home', fn () => new HomeController($container));
				$controllersContainer->set('grading', fn () => new GradingController($container));
				$controllersContainer->set('definition', fn () => new DefinitionController($container));
				$controllersContainer->set('review', fn () => new ReviewController($container));

				return $controllersContainer;
			}
		);
	}
}