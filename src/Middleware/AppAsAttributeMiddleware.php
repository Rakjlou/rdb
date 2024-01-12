<?php
namespace Rdb\Middleware;

use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Rdb\Controller\ControllerInterface;

class AppAsAttributeMiddleware
{
	public function __construct(
		protected App $app,
	) {}

	public function __invoke(Request $request, RequestHandler $handler)
	{
		return $handler->handle($request->withAttribute('app', $this->app));
	}
}
