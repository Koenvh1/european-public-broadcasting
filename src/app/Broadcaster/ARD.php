<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class ARD extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        $body = $response->getBody()->getContents();
        preg_match_all('/\"([^"]+\.m3u8)"/', $body, $output_array);
        $video = $output_array[1][0];

        preg_match_all('/subtitleUrl":"([^"]+)"/', $body, $output_array);
        $subtitles = $output_array[1][0];
        $subtitles = file_get_contents($subtitles);
        $subtitles = str_replace("tt:", "", $subtitles);
        $xml = simplexml_load_string($subtitles);
        $vtt = "WEBVTT \n\n";
        foreach ($xml->body->div->p as $p) {
            $begin = ltrim($p["begin"], "1");
            $end = ltrim($p["end"], "1");
            $text = trim(strip_tags($p->asXML()));
            $text = preg_replace("/[\r\n]+/", "\n", $text);

            $vtt .= "$begin --> $end\n";
            $vtt .= "$text\n\n";
        }
        $subtitles = "data:text/vtt;base64," . base64_encode($vtt);
        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/ardmediathek\\.de/i";
    }
}