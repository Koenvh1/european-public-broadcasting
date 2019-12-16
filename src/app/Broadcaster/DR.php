<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class DR extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/\/([^\/]+?)($|#)/', $url, $output_array);
        $id = $output_array[1][0];
        $response = $this->client->request("GET", "https://www.dr.dk/mu-online/api/1.4/programcard/$id?expanded=true");
        $data = json_decode($response->getBody(), true);
        $video = $data["PrimaryAsset"]["Links"][1]["Uri"];
        $subtitles = $data["PrimaryAsset"]["Subtitleslist"][0]["Uri"];

        if ($video == null) {
            $encrypted = $data["PrimaryAsset"]["Links"][1]["EncryptedUri"];
            $n = hexdec(substr($encrypted, 2, 8));
            $a = substr($encrypted, 10 + $n);
            $data = hex2bin(substr($encrypted, 10, $n));
            $key = hex2bin(hash("sha256",  $a . ":sRBzYNXBzkKgnjj8pGtkACch"));
            $iv = hex2bin($a);

            $video = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        }

        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/dr\\.dk\\/drtv/i";
    }
}