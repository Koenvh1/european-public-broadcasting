<?php


namespace Koenvh\PublicBroadcasting\Controller;


use ErrorException;
use Google\Cloud\Translate\V2\TranslateClient;
use Slim\Http\Request;
use Slim\Http\Response;

class TranslateController
{
    function __invoke(Request $request, Response $response, $args)
    {
        $tr = new \Stichoza\GoogleTranslate\GoogleTranslate();

        $params = json_decode(file_get_contents("php://input"), true);

        try {
            $translated = $tr->setSource($params["source"])->setTarget($params["target"])->translate($params["text"]);
        } catch (ErrorException $e) {
            $translated = $params["text"];
        }

        $response = $response->withHeader("Content-Type", "application/json");
        $response->getBody()->write(json_encode([
            "result" => $translated
        ]));
        return $response;
    }
}
