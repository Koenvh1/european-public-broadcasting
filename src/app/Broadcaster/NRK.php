<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class NRK extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        preg_match_all('/data-program-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://psapi.nrk.no/playback/manifest/program/$id?eea-portability=true&apiKey=d1381d92278a47c09066460f2522a67d");
//        $response = $this->client->request("GET", "https://psapi-we.nrk.no/programs/$id?apiKey=d1381d92278a47c09066460f2522a67d");
        $data = json_decode($response->getBody(), true);
        $video = $data["playable"]["assets"][0]["url"];
        $subtitles = $data["playable"]["subtitles"][0]["webVtt"];
        foreach ($data["playable"]["subtitles"] as $subtitle) {
            if ($subtitle["type"] == "ttv") {
                $subtitles = $subtitle["webVtt"];
                break;
            }
        }
        $response = $this->client->request("GET", $subtitles, [
            "http_errors" => false
        ]);

        return new StreamInformation("no", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/tv\\.nrk\\.no/i";
    }
}
