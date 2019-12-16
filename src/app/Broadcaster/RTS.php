<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class RTS extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/id=(\d+)/', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://il.srgssr.ch/integrationlayer/2.0/mediaComposition/byUrn/urn:rts:video:$id.json?onlyChapters=true&vector=portalplay");
        $data = json_decode($response->getBody(), true);
        $video = $data["chapterList"][0]["resourceList"][0]["url"];
        $subtitles = $data["chapterList"][0]["subtitleList"][0]["url"];

        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/rts\\.ch/i";
    }
}