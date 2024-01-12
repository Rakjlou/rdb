<?php
namespace Rdb\Middleware;

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Rdb\Controller\ControllerInterface;

class RoutingMiddleware
{
	public function __invoke(Request $request, RequestHandler $handler)
	{
		$app = $request->getAttribute('app');
		$controllers = $app->getContainer()->get('controller');
		$containerEntries = $controllers->getKnownEntryNames();

		foreach ($app->getContainer()->get('controller')->getKnownEntryNames() as $entry) {
			$controller = $controllers->get($entry);

			if ($controller instanceof ControllerInterface) {
				$controller->route();
			}
		}

		return $handler->handle($request);
	}
}
