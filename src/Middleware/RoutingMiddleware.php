<?php
namespace Rdb\Middleware;

use Slim\App;

use Rdb\Controller\ControllerInterface;

class RoutingMiddleware
{
	public function __construct(
		protected App $app,
	) {}

	public function __invoke($request, $handler)
	{
		$controllers = $this->app->getContainer()->get('controller');
		$containerEntries = $controllers->getKnownEntryNames();

		foreach ($this->app->getContainer()->get('controller')->getKnownEntryNames() as $entry) {
			$controller = $controllers->get($entry);

			if ($controller instanceof ControllerInterface) {
				$controller->route();
			}
		}

		return $handler->handle($request);
	}
}
