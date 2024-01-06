<?php
namespace Rdb\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends AbstractController
{
    static public function routes($app)
    {
        $app->get('/', [self::class, 'home'])->setName('home');
    }

    public function home(Request $request, Response $response, array $args)
    {
        return $this->view->render($response, 'home.twig', ['name' => 'Noé']);
    }
}
