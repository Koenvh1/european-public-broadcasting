<?php
//$options = array(
//    'http' => array(
//        'header'  => "Content-type: application/json\r\n",
//        'method'  => 'POST',
//        'content' => file_get_contents("php://input")
//    )
//);
//$context  = stream_context_create($options);
//$result = file_get_contents("https://www2.deepl.com/jsonrpc", false, $context);
//foreach ($http_response_header as $header) {
//    header($header);
//}


require_once ("vendor/autoload.php");
$tr = new \Stichoza\GoogleTranslate\TranslateClient();

$params = json_decode(file_get_contents("php://input"), true);

echo json_encode([
    "result" => $tr->setTarget($params["target"])->translate($params["text"])
]);