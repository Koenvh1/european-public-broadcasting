<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class SVT extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        if (strpos($url, "id") !== false) {
            $urlComponents = parse_url($url);
            parse_str($urlComponents["query"], $params);
            $id = $params["id"];
        } else {
            $response = $this->client->request("GET", $url);
            preg_match_all('/"videoSvtId\\\\":\\\\"(.+?)\\\\"/', $response->getBody()->getContents(), $output_array);
            $id = $output_array[1][0];
        }
        $response = $this->client->request("GET", "https://api.svt.se/video/$id");
        $data = json_decode($response->getBody()->getContents(), true);
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

        return new StreamInformation("sv", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/svtplay\\.se/i";
    }
}
