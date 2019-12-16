<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require 'vendor/autoload.php';

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true,
        "addContentLengthHeader" => false
    ]
]);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('templates', [
        'cache' => false
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
    $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

    return $view;
};

$app->get("/[home]", \Koenvh\PublicBroadcasting\Controller\IndexController::class);
$app->get("/video", \Koenvh\PublicBroadcasting\Controller\VideoController::class);
$app->post("/translate", \Koenvh\PublicBroadcasting\Controller\TranslateController::class);


$app->run();
