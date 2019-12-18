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
        $response = $this->client->request("GET", "https://psapi-we.nrk.no/programs/$id?apiKey=d1381d92278a47c09066460f2522a67d");
        $data = json_decode($response->getBody(), true);
        $video = str_replace("http://", "https://", $data["mediaAssetsOnDemand"][0]["hlsUrl"]);
        $subtitles = urldecode($data["mediaAssetsOnDemand"][0]["timedTextSubtitlesUrl"]);
        $subtitles = str_replace(".ttml", ".vtt", $subtitles);
        $response = $this->client->request("GET", $subtitles, [
            "http_errors" => false
        ]);

        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/tv\\.nrk\\.no/i";
    }
}