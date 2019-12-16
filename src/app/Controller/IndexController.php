<?php


namespace Koenvh\PublicBroadcasting\Controller;


use Slim\Http\Request;
use Slim\Http\Response;

class IndexController
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    function __invoke(Request $request, Response $response, $args)
    {
        return $this->container->view->render($response, "index.twig", []);
    }
}