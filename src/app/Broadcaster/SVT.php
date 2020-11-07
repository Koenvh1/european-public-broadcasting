<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class SVT extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        preg_match_all('/"svtId":\s?"(.+?)"/', $response->getBody()->getContents(), $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://api.svt.se/video/$id");
        $data = json_decode($response->getBody(), true);
        $video = "";
        $subtitles = "";
        foreach ($data["videoReferences"] as $item) {
            if ($item["format"] == "dash-hbbtv") {
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

        return new StreamInformation("sv", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/svtplay\\.se/i";
    }
}
