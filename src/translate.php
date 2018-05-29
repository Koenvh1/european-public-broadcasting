<?php
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => file_get_contents("php://input")
    )
);
$context  = stream_context_create($options);
$result = file_get_contents("https://www.deepl.com/jsonrpc", false, $context);
foreach ($http_response_header as $header) {
    header($header);
}
echo $result;