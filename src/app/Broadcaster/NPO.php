<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class NPO extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $videoId = explode("/", $url);
        $videoId = end($videoId);

        $response = $this->client->request("GET", "https://www.npostart.nl/api/token", [
            "headers" => [
                "X-Requested-With" => "XMLHttpRequest",
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36",
            ]
        ]);
        $token = "";
        $xsrfToken = "";
        foreach ($response->getHeader("Set-Cookie") as $item) {
            if (preg_match('/XSRF-TOKEN=([^;]*);/', $item, $output_array)) {
                $xsrfToken = $output_array[1];
            }
            if (preg_match('/npo_session=([^;]*);/', $item, $output_array)) {
                $token = $output_array[1];
            }
        }

        $response = $this->client->request("POST", "https://www.npostart.nl/player/$videoId", [
            "headers" => [
                "X-Requested-With" => "XMLHttpRequest",
                "X-Xsrf-Token" => urldecode($xsrfToken),
                "Content-Type" => "application/x-www-form-urlencoded",
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36",
                "Cookie" => "npo_session=$token",
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
//                "_token" => $token,
            ]
        ]);

        $player = json_decode($response->getBody()->getContents(), true);
        $token = $player["token"];

        $embed = $this->client->request("GET", $player["embedUrl"], [
            "headers" => [
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36",
            ]
        ]);

        $response = $this->client->request("POST", "https://start-player.npo.nl/video/$videoId/streams?profile=dash-widevine&quality=npo&tokenId=$token&streamType=broadcast&isYospace=0&videoAgeRating=null&isChromecast=0&mobile=0&ios=0", [
            "headers" => [
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36",
                "Host" => "start-player.npo.nl",
                "Origin" => "https://start-player.npo.nl",
                "Referer" => $player["embedUrl"]
            ],
            "http_errors" => false
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        return new StreamInformation("nl", $response["stream"]["src"], "https://assetscdn.npostart.nl/subtitles/original/nl/$videoId.vtt", [
            "com.widevine.alpha" => [
                "serverURL" => $response["stream"]["keySystemOptions"][0]["options"]["licenseUrl"],
                "httpRequestHeaders" => $response["stream"]["keySystemOptions"][0]["options"]["httpRequestHeaders"],
            ]
        ]);
    }

    static function getRegex(): string
    {
        return "/npostart\\.nl/i";
    }
}
