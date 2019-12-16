<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class TVP extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        preg_match_all('/"playerContainer"\s+data-id="(.+?)"/', $response->getBody()->getContents(), $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://vod.tvp.pl/sess/tvplayer.php?object_id=$id&autoplay=true&nextprev=1");
        $body = $response->getBody()->getContents();
        preg_match_all('/\'(.+\.mp4)\'/', $body, $output_array);
        $video = $output_array[1][0];
        preg_match_all('/"(.+\.xml)"/', $body, $output_array);
        $subtitles = file_get_contents("https:" . $output_array[1][0]);
        $xml = simplexml_load_string($subtitles);

        function vttTime($seconds) {
            $seconds = explode(".", $seconds);
            $t = $seconds[0];
            return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60) . "." . $seconds[1];
        }

        $vtt = "WEBVTT \n\n";
        foreach ($xml->body->div->p as $p) {
            $begin = vttTime(rtrim($p["begin"], "s"));
            $end = vttTime(rtrim($p["end"], "s"));
            $text = trim(strip_tags($p->asXML()));
            $text = preg_replace("/[\r\n]+/", "\n", $text);

            $vtt .= "$begin --> $end\n";
            $vtt .= "$text\n\n";
        }
        $subtitles = "data:text/vtt;base64," . base64_encode($vtt);

        return new StreamInformation("https://cors-anywhere.herokuapp.com/$video", $subtitles);
    }

    static function getRegex(): string
    {
        return "/vod.tvp.pl/i";
    }
}