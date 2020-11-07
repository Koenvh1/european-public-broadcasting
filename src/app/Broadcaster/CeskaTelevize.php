<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class CeskaTelevize extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('#\/(\d+)\/#', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("POST", "https://www.ceskatelevize.cz/ivysilani/ajax/get-client-playlist/", [
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
                "type" => "html",
                "canPlayDRM" => "true"
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        $response = $this->client->request("GET", $data["url"]);
        $data = json_decode($response->getBody(), true);

        $subtitlesUrl = $data["playlist"][0]["subtitles"][0]["url"];
        $videoUrl = $data["playlist"][0]["streamUrls"]["main"];

        return new StreamInformation("cs", $videoUrl, $subtitlesUrl);
    }

    static function getRegex(): string
    {
        return "/ceskatelevize\\.cz/i";
    }
}
