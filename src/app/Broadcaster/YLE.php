<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class YLE extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/(1-\d+)/', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://external.api.yle.fi/v1/programs/items/$id.json?app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");
        $data = json_decode($response->getBody(), true);
        $publicationEvent = null;
        foreach ($data["data"]["publicationEvent"] as $item) {
            if (isset($item["media"])) {
                $publicationEvent = $item;
                break;
            }
        }
        $mediaId = $publicationEvent["media"]["id"];
        $response = $this->client->request("GET", "https://external.api.yle.fi/v1/media/playouts.json?program_id=$id&media_id=$mediaId&protocol=HLS&app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");
        $data = json_decode($response->getBody(), true);
        //$subtitles = $data["data"][0]["subtitles"][0]["uri"];
        $video = $data["data"][0]["url"];

        $decryptKey = "7895f030eea0ba81";
        $tmp = base64_decode($video);
        $tmp = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $decryptKey, substr($tmp, 16), MCRYPT_MODE_CBC, substr($tmp, 0, 16));
        $video = substr($tmp, 0, -ord($tmp[strlen($tmp)-1]));

        $response = $this->client->request("GET", "https://external.api.yle.fi/v1/tracking/streamstart?program_id=$id&media_id=$mediaId&app_id=b7a3c2a4&app_key=fe3bfffe34a6ae2e3b972af1a4bf1592");

        return new StreamInformation($video, "");
    }

    static function getRegex(): string
    {
        return "/areena\\.yle\\.fi/i";
    }
}