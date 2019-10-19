<?php
require_once "vendor/autoload.php";

$client = new \GuzzleHttp\Client([
    "headers" => [
        "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0"
    ]
]);
$url = $_GET["url"];

function ard()
{
    global $client, $url;
    $response = $client->request("GET", $url);
    $body = $response->getBody()->getContents();
    preg_match_all('/\"([^"]+\.m3u8)"/', $body, $output_array);
    $video = $output_array[1][0];

    preg_match_all('/subtitleUrl":"([^"]+)"/', $body, $output_array);
    $subtitles = $output_array[1][0];
    $subtitles = file_get_contents($subtitles);
    $subtitles = str_replace("tt:", "", $subtitles);
    $xml = simplexml_load_string($subtitles);
    $vtt = "WEBVTT \n\n";
    foreach ($xml->body->div->p as $p) {
        $begin = ltrim($p["begin"], "1");
        $end = ltrim($p["end"], "1");
        $text = trim(strip_tags($p->asXML()));
        $text = preg_replace("/[\r\n]+/", "\n", $text);

        $vtt .= "$begin --> $end\n";
        $vtt .= "$text\n\n";
    }
    $subtitles = "data:text/vtt;base64," . base64_encode($vtt);
    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

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

function dr()
{
    global $client, $url;
    preg_match_all('/\/([^\/]+?)($|#)/', $url, $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://www.dr.dk/mu-online/api/1.4/programcard/$id?expanded=true");
    $data = json_decode($response->getBody(), true);
    $video = $data["PrimaryAsset"]["Links"][1]["Uri"];
    $subtitles = $data["PrimaryAsset"]["Subtitleslist"][0]["Uri"];

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function err()
{
    global $client, $url;
    $response = $client->request("GET", $url);
    //if (strpos($url, "etv2.") !== false) {
        preg_match_all('/],(.+),"programName"/', $response->getBody()->getContents(), $output_array);
        $stream = "{" . $output_array[1][0] . "}";
        $data = json_decode($stream, true);
        $video = $data["media"]["src"]["file"];
        $subtitles = $data["media"]["subtitles"][0]["src"];
//    } else {
//        preg_match_all('/sources:\s"(.+)"/', $response->getBody()->getContents(), $output_array);
//        $stream = $output_array[1][0];
//        $response = $client->request("GET", "https://services.err.ee/api/media/mediaData?stream=" . urlencode($stream));
//        $data = json_decode($response->getBody(), true);
//        $video = $data["media"]["src"]["file"];
//        foreach ($subtitles = $data["subtitles"] as $key => $value) {
//            $subtitles = "https://services.err.ee/subtitles/file/" . $value["id"] . "/" . $value["id"] . "_$key.vtt";
//            break;
//        }
//    }

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function npo() {
    global $client, $url;

    $videoId = explode("/", $url);
    $videoId = end($videoId);

    $response = $client->request("GET", "https://www.npostart.nl/api/token", [
        "headers" => [
            "X-Requested-With" => "XMLHttpRequest",
            "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"]
        ]
    ]);
    $token = json_decode($response->getBody()->getContents(), true)["token"];
    $xsrfToken = $response->getHeader("Set-Cookie");
    foreach ($xsrfToken as $item) {
        if (preg_match('/npo_session=([^;]*);/', $item, $output_array)) {
            $xsrfToken = $output_array[1];
            break;
        }
    }

    $response = $client->request("POST", "https://www.npostart.nl/player/$videoId", [
        "headers" => [
            "X-Requested-With" => "XMLHttpRequest",
            "X-XSRF-TOKEN" => urldecode($xsrfToken),
            "Content-Type" => "application/x-www-form-urlencoded",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0",
            "Cookie" => "npo_session=$xsrfToken",
            "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"]
        ],
        "form_params" => [
            "autoplay" => "0",
            "progress" => "0",
            "mediaId" => $videoId,
            "trackProgress" => "1",
            "share" => "1",
            "pageUrl" => "http://www.npostart.nl/nederland-van-boven/21-11-2013/VPWON_1184655",
            "hasAdConsent" => "0",
            "_token" => $token,
        ]
    ]);
    $token = json_decode($response->getBody()->getContents(), true)["token"];

    $response = $client->request("GET", "https://start-player.npo.nl/video/$videoId/streams?profile=dash-widevine&quality=npo&tokenId=$token&streamType=broadcast&mobile=0&ios=0&isChromecast=0", [
        "headers" => [
            "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"]
        ]
    ]);
    $response = json_decode($response->getBody()->getContents(), true);

    echo json_encode([
        "subtitles" => "https://rs.poms.omroep.nl/v1/api/subtitles/" . $videoId . "/nl_NL/CAPTION.vtt",
        "video" => $response["stream"]["src"],
        "protection" => [
            "com.widevine.alpha" => [
                "serverURL" => $response["stream"]["keySystemOptions"][0]["options"]["licenseUrl"],
                "httpRequestHeaders" => $response["stream"]["keySystemOptions"][0]["options"]["httpRequestHeaders"],
            ]
        ]
    ]);
}

function nrk()
{
    global $client, $url;
    $response = $client->request("GET", $url);
    preg_match_all('/data-program-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://psapi-we.nrk.no/programs/$id?apiKey=d1381d92278a47c09066460f2522a67d");
    $data = json_decode($response->getBody(), true);
    $video = str_replace("http://", "https://", $data["mediaAssetsOnDemand"][0]["hlsUrl"]);
    $subtitles = "https://undertekst.nrk.no/prod/" . substr($id, 0, 6) . "/00/$id/TTV/$id.vtt";
    $response = $client->request("GET", $subtitles, [
        "http_errors" => false
    ]);
    if ($response->getStatusCode() >= 400) {
        $subtitles = "https://undertekst.nrk.no/prod/" . substr($id, 0, 6) . "/00/$id/NOR/$id.vtt";
    }

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function rtbf()
{
    global $client, $url;
    preg_match_all('/id=(\d+)/', $url, $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://www.rtbf.be/auvio/embed/media?id=$id&autoplay=1");
    preg_match_all('/data-media="(.+?)"/', $response->getBody(), $output_array);
    $data = $output_array[1][0];
    $data = html_entity_decode($data);
    $data = json_decode($data, true);
    $video = $data["urlHls"];
    $subtitles = $data["tracks"]["fsm"]["url"];

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function rts()
{
    global $client, $url;
    preg_match_all('/id=(\d+)/', $url, $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://il.srgssr.ch/integrationlayer/2.0/mediaComposition/byUrn/urn:rts:video:$id.json?onlyChapters=true&vector=portalplay");
    $data = json_decode($response->getBody(), true);
    $video = $data["chapterList"][0]["resourceList"][0]["url"];
    $subtitles = $data["chapterList"][0]["subtitleList"][0]["url"];

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

function svt()
{
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

function tvp()
{
    global $client, $url;
    $response = $client->request("GET", $url);
    preg_match_all('/"playerContainer"\s+data-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://vod.tvp.pl/sess/tvplayer.php?object_id=$id&autoplay=true&nextprev=1");
    $body = $response->getBody()->getContents();
    preg_match_all('/\'(.+\.mp4)\'/', $body, $output_array);
    $video = $output_array[1][0];
    preg_match_all('/"(.+\.xml)"/', $body, $output_array);
    $subtitles = file_get_contents("https:" . $output_array[1][0]);
    $xml = simplexml_load_string($subtitles);

    function vttTime($seconds) {
        $seconds = explode(".", $seconds);
        $t = $seconds[0];
        return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60) . "." . $seconds[1];
    }

    $vtt = "WEBVTT \n\n";
    foreach ($xml->body->div->p as $p) {
        $begin = vttTime(rtrim($p["begin"], "s"));
        $end = vttTime(rtrim($p["end"], "s"));
        $text = trim(strip_tags($p->asXML()));
        $text = preg_replace("/[\r\n]+/", "\n", $text);

        $vtt .= "$begin --> $end\n";
        $vtt .= "$text\n\n";
    }
    $subtitles = "data:text/vtt;base64," . base64_encode($vtt);

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => "https://cors-anywhere.herokuapp.com/$video"
    ]);
}

function yle()
{
    global $client, $url;
    preg_match_all('/(1-\d+)/', $url, $output_array);
    $id = $output_array[1][0];
    $response = $client->request("GET", "https://external.api.yle.fi/v1/programs/items/$id.json?app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");
    $data = json_decode($response->getBody(), true);
    $publicationEvent = null;
    foreach ($data["data"]["publicationEvent"] as $item) {
        if (isset($item["media"])) {
            $publicationEvent = $item;
            break;
        }
    }
    $mediaId = $publicationEvent["media"]["id"];
    $response = $client->request("GET", "https://external.api.yle.fi/v1/media/playouts.json?program_id=$id&media_id=$mediaId&protocol=HLS&app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");
    $data = json_decode($response->getBody(), true);
    //$subtitles = $data["data"][0]["subtitles"][0]["uri"];
    $video = $data["data"][0]["url"];

    $decryptKey = "7895f030eea0ba81";
    $tmp = base64_decode($video);
    $tmp = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $decryptKey, substr($tmp, 16), MCRYPT_MODE_CBC, substr($tmp, 0, 16));
    $video = substr($tmp, 0, -ord($tmp[strlen($tmp)-1]));

    $response = $client->request("GET", "https://external.api.yle.fi/v1/tracking/streamstart?program_id=$id&media_id=$mediaId&app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");

    echo json_encode([
        "subtitles" => "",
        "video" => $video
    ]);
}

function zdf()
{
    global $client, $url;
    $response = $client->request("GET", $url);
    preg_match_all('/"content":\s"(.+?)"/', $response->getBody(), $output_array);
    $apiUrl = $output_array[1][0];
    $response = $client->request("GET", $apiUrl, [
        "headers" => [
            "Api-Auth" => "Bearer 2fff3ea743c72b26a40a9bc60c32978681ecae8b"
        ]
    ]);
    $data = json_decode($response->getBody(), true);
    $id = $data["tracking"]["nielsen"]["content"]["assetid"];

    $response = $client->request("GET", "https://api.zdf.de/tmd/2/ngplayer_2_3/vod/ptmd/mediathek/$id", [
        "headers" => [
            "Api-Auth" => "Bearer 2fff3ea743c72b26a40a9bc60c32978681ecae8b"
        ]
    ]);
    $data = json_decode($response->getBody(), true);

    $video = $data["priorityList"][0]["formitaeten"][0]["qualities"][0]["audio"]["tracks"][0]["uri"];
    $subtitles = $data["captions"][1]["uri"];

    echo json_encode([
        "subtitles" => $subtitles,
        "video" => $video
    ]);
}

switch ($_GET["broadcaster"]) {
    case "ard":
        ard();
        break;
    case "ceskatelevize":
        ceskatelevize();
        break;
    case "dr":
        dr();
        break;
    case "err":
        err();
        break;
    case "npo":
        npo();
        break;
    case "nrk":
        nrk();
        break;
    case "rtbf":
        rtbf();
        break;
    case "rts":
        rts();
        break;
    case "svt":
        svt();
        break;
    case "tvp":
        tvp();
        break;
    case "yle":
        yle();
        break;
    case "zdf":
        zdf();
        break;
    default:
        echo json_encode([
            "error" => "Broadcaster not found"
        ]);
}