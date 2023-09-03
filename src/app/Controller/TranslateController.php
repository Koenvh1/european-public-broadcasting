<?php


namespace Koenvh\PublicBroadcasting\Controller;


use DeepL\Translator;
use ErrorException;
use Google\Cloud\Translate\V2\TranslateClient;
use GuzzleHttp\Client;
use Slim\Http\Request;
use Slim\Http\Response;

class TranslateController
{
    function __invoke(Request $request, Response $response, $args)
    {
        $params = json_decode(file_get_contents("php://input"), true);

        if ($params["source"] == $params["target"]) {
            $response->getBody()->write(json_encode([
                "result" => $params["text"]
            ]));
        } else {
            $deepl = new Translator(DEEPL_KEY);
            $translated = $deepl->translateText($params["text"], $params["source"], $params["target"]);

            $response = $response->withHeader("Content-Type", "application/json");
            $response->getBody()->write(json_encode([
                "result" => html_entity_decode($translated, ENT_QUOTES | ENT_XML1, 'UTF-8')
            ]));

//            $tr = new TranslateClient([
//                "key" => GOOGLE_TRANSLATE_KEY
//            ]);
//
//            $translated = $tr->translate($params["text"], [
//                "source" => $params["source"],
//                "target" => $params["target"]
//            ])["text"];
//
//            $response = $response->withHeader("Content-Type", "application/json");
//            $response->getBody()->write(json_encode([
//                "result" => html_entity_decode($translated, ENT_QUOTES | ENT_XML1, 'UTF-8')
//            ]));
        }
        return $response;
    }
}
