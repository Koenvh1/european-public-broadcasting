<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class RTBF extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/id=(\d+)/', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://www.rtbf.be/auvio/embed/media?id=$id&autoplay=1");
        preg_match_all('/data-media="(.+?)"/', $response->getBody(), $output_array);
        $data = $output_array[1][0];
        $data = html_entity_decode($data);
        $data = json_decode($data, true);
        $video = $data["urlHls"];
        $subtitles = $data["tracks"]["fsm"]["url"];

        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/rtbf\\.be/i";
    }
}