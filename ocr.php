<?php
ini_set("display_errors", 1);

$json = ['requests' =>
    [
        [
            'features' => [
                [
                    'maxResults' => 1,
                    'type' => 'DOCUMENT_TEXT_DETECTION',
                ],
            ],
            'image' => [
                'content' => file_get_contents("php://input"),
            ],
            /*
            'imageContext' => [
                'cropHintsParams' => [
                    'aspectRatios' => [
                        1
                    ],
                ],
            ],
            */
        ],
    ],
];
//AIzaSyBK0HnW224uvV6jHWL6Dyn5KcjV6Iu24Bc
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($json)
    ),
);
$context  = stream_context_create($options);
$result = file_get_contents("https://cxl-services.appspot.com/proxy?url=https%3A%2F%2Fvision.googleapis.com%2Fv1%2Fimages%3Aannotate", false, $context);
foreach ($http_response_header as $header) {
    header($header);
}
echo $result;