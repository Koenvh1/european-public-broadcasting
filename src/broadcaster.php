<?php
require_once "vendor/autoload.php";

$client = new \GuzzleHttp\Client([
    "headers" => [
        "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0"
    ]
]);
$url = $_GET["url"];

function ceskatelevize()
{
    global $client, $url;
    preg_match_all('#\/(\d+)\/#', $url, $output_array);
    $id = $output_array[1][0];
    //echo "https:" . $urlIframe;
    //echo "\n";
    //echo "https://www.ceskatelevize.cz/ivysilani/embed/iFramePlayer.php?hash=bc84be37667151d4cd8336faba4eaaf8deb3cf65&IDEC=219 411 05803/0110&channelID=3&width=100%";
    $response = $client->request("POST", "https://www.ceskatelevize.cz/ivysilani/ajax/get-client-playlist/", [
        "headers" => [
            "x-addr" => "127.0.0.1"
        ],
        "form_params" => [
            "playlist[0][type]" => "episode",
            "playlist[0][id]" => "$id",
            "playlist[0][startTime]" => "",
            "playlist[0][stopTime]" => "",
            "requestUrl" => "/ivysilani/embed/iFramePlayer.php",
            "requestSource" => "iVysilani",
            "type" => "html"
        ]
    ]);

    $data = json_decode($response->getBody(), true);

    $response = $client->request("GET", $data["url"]);
    $data = json_decode($response->getBody(), true);

    $subtitlesUrl = $data["playlist"][0]["subtitles"][0]["url"];
    $videoUrl = $data["playlist"][0]["streamUrls"]["main"];

    echo json_encode([
        "subtitles" => $subtitlesUrl,
        "video" => $videoUrl
    ]);
}

function err() {
    global $client, $url;
    $response = $client->request("GET", $url);
    if (strpos($url, "etv2.") !== false) {
        preg_match_all('/],(.+),"programName"/', $response->getBody()->getContents(), $output_array);
        $stream = "{" . $output_array[1][0] . "}";
        $data = json_decode($stream, true);
        $video = $data["media"]["src"]["file"];
        $subtitles = $data["media"]["subtitles"][0]["src"];
    } else {
        preg_match_all('/sources:\s"(.+)"/', $response->getBody()->getContents(), $output_array);
        $stream = $output_array[1][0];
        $response = $client->request("GET", "https://services.err.ee/api/media/mediaData?stream=" . urlencode($stream));
        $data = json_decode($response->getBody(), true);
        $video = $data["media"]["src"]["file"];
        foreach ($subtitles = $data["subtitles"] as $key => $value) {
            $subtitles = "https://services.err.ee/subtitles/file/" . $value["id"] . "/" . $value["id"] . "_$key.vtt";
            break;
        }
    }

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function nrk() {
    global $client, $url;
    $response = $client->request("GET", $url);
    preg_match_all('/data-program-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://psapi-we.nrk.no/programs/$id?apiKey=d1381d92278a47c09066460f2522a67d");
    $data = json_decode($response->getBody(), true);
    $video = str_replace("http://", "https://", $data["mediaAssetsOnDemand"][0]["hlsUrl"]);
    $subtitles = "https://undertekst.nrk.no/prod/" . substr($id, 0, 6) . "/00/$id/TTV/$id.vtt";

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function svt() {
    global $client, $url;
    $response = $client->request("GET", $url);
    preg_match_all('/data-video-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://api.svt.se/videoplayer-api/video/$id");
    $data = json_decode($response->getBody(), true);
    $video = "";
    $subtitles = "";
    foreach ($data["videoReferences"] as $item) {
        if ($item["format"] == "dashhbbtv") {
            $video = $item["url"];
            break;
        } elseif ($item["format"] == "hls") {
            $video = $item["url"];
        }
    }
    foreach ($data["subtitleReferences"] as $item) {
        if ($item["format"] == "webvtt") {
            $subtitles = $item["url"];
            break;
        }
    }

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

switch ($_GET["broadcaster"]) {
    case "ceskatelevize":
        ceskatelevize();
        break;
    case "err":
        err();
        break;
    case "nrk":
        nrk();
        break;
    case "svt":
        svt();
        break;
    default:
        echo json_encode([
            "error" => "Broadcaster not found"
        ]);
}