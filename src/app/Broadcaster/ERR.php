<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class ERR extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        preg_match_all('/],(.+),"programName"/', $response->getBody()->getContents(), $output_array);
        $stream = "{" . $output_array[1][0] . "}";
        $data = json_decode($stream, true);
        $video = $data["media"]["src"]["file"];
        $subtitles = $data["media"]["subtitles"][0]["src"];

        return new StreamInformation("et", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/err\\.ee/i";
    }
}
