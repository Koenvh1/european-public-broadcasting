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
                "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
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
                "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
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
                "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
            ]
        ]);

        $profile = "dash-widevine";
        if (preg_match('/Edg\//', $_SERVER["HTTP_USER_AGENT"], $output_array)) {
            $profile = "dash-playready";
        }

        $response = $this->client->request("POST", "https://start-player.npo.nl/video/$videoId/streams?profile=$profile&quality=npo&tokenId=$token&streamType=broadcast&isYospace=0&videoAgeRating=null&isChromecast=0&mobile=0&ios=0", [
            "headers" => [
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"],
                "User-Agent" => $_SERVER["HTTP_USER_AGENT"],
                "Host" => "start-player.npo.nl",
                "Origin" => "https://start-player.npo.nl",
                "Referer" => $player["embedUrl"]
            ],
            "http_errors" => false
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        return new StreamInformation("nl", $response["stream"]["src"], "https://assetscdn.npostart.nl/subtitles/original/nl/$videoId.vtt", [
            $response["stream"]["keySystemOptions"][0]["name"] => [
                "serverURL" => $response["stream"]["keySystemOptions"][0]["options"]["licenseUrl"],
                "httpRequestHeaders" => $response["stream"]["keySystemOptions"][0]["options"]["httpRequestHeaders"],
            ],
        ]);
    }

    static function getRegex(): string
    {
        return "/npostart\\.nl/i";
    }
}
