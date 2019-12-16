<?php


namespace Koenvh\PublicBroadcasting\Controller;


use Koenvh\PublicBroadcasting\Broadcaster\Broadcaster;
use Koenvh\PublicBroadcasting\InvalidURLException;
use Slim\Http\Request;
use Slim\Http\Response;

class VideoController
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    function __invoke(Request $request, Response $response, $args)
    {
        try {
            $streamInfo = Broadcaster::getStreamInformation($_GET["v"]);
        } catch (InvalidURLException $e) {
            return $this->container->view->render($response, "404.twig", []);
        }
        return $this->container->view->render($response, "video.twig", [
            "video" => $streamInfo->getVideoUrl(),
            "caption" => $streamInfo->getCaptionUrl(),
            "protection" => $streamInfo->getDrmData()
        ]);
    }
}