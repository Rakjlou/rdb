<?php
namespace Rdb;

use DI\Container;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use Rdb\Db\SQLiteDatabase;

class ContainerFactory
{
	static public function get(): Container
	{
		$container = new Container;

		self::setView($container);
		self::setFlash($container);
		self::setDb($container);

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

	static protected function setView(Container $container): void
	{
		$container->set(
			'view',
			function ()
			{
				$twig = Twig::create(
					dirname(__DIR__) . DIRECTORY_SEPARATOR . 'view',
					['cache' => false]
				);

				$twig->getEnvironment()->addGlobal('site', [
					'lang' => 'en'
				]);

				return $twig;
			}
		);
	}
}
