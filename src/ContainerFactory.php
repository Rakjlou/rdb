<?php
namespace Rdb;

use DI\Container;
use Slim\Views\Twig;
use Slim\Flash\Messages;

use Rdb\Db\SQLiteDatabase;
use Rdb\Repository\DefinitionRepository;

class ContainerFactory
{
	static public function get(): Container
	{
		$container = new Container();

		self::setFlash($container);
		self::setDb($container);
		self::setRepository($container);
		self::setView($container);

		return $container;
	}

	static protected function setFlash(Container $container): void
	{
		$container->set('flash', fn() => new Messages());
	}

	static protected function setDb(Container $container): void
	{
		$container->set(
			'db',
			fn() => new SQLiteDatabase(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'data', 'database.sqlite']))
		);
	}

	static protected function setRepository(Container $container): void
	{
		$container->set(
			'repository',
			function () use ($container)
			{
				$repositoryContainer = new Container();

				$repositoryContainer->set(
					'reviewableDefinition',
					fn () => new DefinitionRepository($container->get('db'))
				);

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
					dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view',
					[
						'cache' => false,
						'debug' => true
					]
				);

				$twig->getEnvironment()->addGlobal('flash', $container->get('flash'));
				$twig->getEnvironment()->addGlobal('site', [
					'lang' => 'en',
				]);
				$twig->addExtension(new \Twig\Extension\DebugExtension());

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
}
