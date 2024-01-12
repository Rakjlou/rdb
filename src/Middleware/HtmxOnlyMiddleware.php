<?php
namespace Rdb\Middleware;

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Rdb\Controller\ControllerInterface;

class HtmxOnlyMiddleware
{
	public function __construct(
		protected App $app,
	) {}

	public function __invoke(Request $request, RequestHandler $handler)
	{
		$htmxHeader = $request->getHeaderLine('HX-Request');

		if ($htmxHeader === 'true')
			return $handler->handle($request);
		else
		{
			$response = $this->app->getResponseFactory()->createResponse();
			return $response->withStatus(403);
		}
	}
}
