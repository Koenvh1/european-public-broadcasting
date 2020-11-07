<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class ZDF extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        $body = $response->getBody();
        preg_match_all('/"content":\s"(.+?)"/', $body, $output_array);
        $apiUrl = $output_array[1][0];
        preg_match_all('/"apiToken":\s"(.+?)"/', $body, $output_array);
        $apiToken = $output_array[1][0];
        $response = $this->client->request("GET", $apiUrl, [
            "headers" => [
                "Api-Auth" => "Bearer $apiToken"
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        $id = $data["tracking"]["nielsen"]["content"]["assetid"];

        $response = $this->client->request("GET", "https://api.zdf.de/tmd/2/ngplayer_2_3/vod/ptmd/mediathek/$id", [
            "headers" => [
                "Api-Auth" => "Bearer $apiToken"
            ]
        ]);
        $data = json_decode($response->getBody(), true);

        $video = $data["priorityList"][0]["formitaeten"][0]["qualities"][0]["audio"]["tracks"][0]["uri"];
        $subtitles = $data["captions"][1]["uri"];

        return new StreamInformation("de", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/zdf.de/i";
    }
}
