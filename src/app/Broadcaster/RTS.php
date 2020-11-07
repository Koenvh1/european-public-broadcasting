<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\InvalidURLException;
use Koenvh\PublicBroadcasting\StreamInformation;

class RTS extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/id=(\d+)/', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://il.srgssr.ch/integrationlayer/2.0/mediaComposition/byUrn/urn:rts:video:$id.json?onlyChapters=false&vector=portalplay");
        $data = json_decode($response->getBody(), true);
        $video = $data["chapterList"][0]["resourceList"][0]["url"];

        $response = $this->client->request("GET", $video);
        $data = $response->getBody()->getContents();
        preg_match_all('/URI="([^"]+)"/', $data, $output_array);
        $subtitleUrl = $output_array[1][0];
        if ($subtitleUrl == null) {
            throw new InvalidURLException();
        }
        $response = $this->client->request("GET", $subtitleUrl);
        $data = $response->getBody()->getContents();

        $vtt = "WEBVTT \n\n";
        foreach (explode("\n", $data) as $line) {
            if (strpos($line, "#") === 0) continue;

            $data = file_get_contents($line);
            $data = implode("\n", array_slice(explode("\n", $data), 4));
            $vtt .= $data;
            $vtt .= "\n\n";
        }

        $subtitles = "data:text/vtt;base64," . base64_encode($vtt);
        return new StreamInformation("fr", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/rts\\.ch/i";
    }
}
