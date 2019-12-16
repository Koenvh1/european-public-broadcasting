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

        $response = $this->client->request("POST", "https://www.npostart.nl/player/$videoId", [
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

        $response = $this->client->request("GET", "https://start-player.npo.nl/video/$videoId/streams?profile=dash-widevine&quality=npo&tokenId=$token&streamType=broadcast&mobile=0&ios=0&isChromecast=0", [
            "headers" => [
                "X-Forwarded-For" => $_SERVER["REMOTE_ADDR"]
            ]
        ]);
        $response = json_decode($response->getBody()->getContents(), true);

        return new StreamInformation($response["stream"]["src"], "https://rs.poms.omroep.nl/v1/api/subtitles/" . $videoId . "/nl_NL/CAPTION.vtt", [
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