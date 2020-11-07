<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class DR extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        preg_match_all('/\/([^\/]+?)($|#)/', $url, $output_array);
        $id = $output_array[1][0];
        $id = explode("_", $id);
        $id = end($id);


        $response = $this->client->request("POST", "https://isl.dr-massive.com/api/authorization/anonymous-sso?device=web_browser&ff=idp%2Cldp&lang=da", [
            "json" => [
                "deviceId" => "dc72987a-bcb8-4ed1-a3a8-56ae4aaaf9b3",
                "optout" => true,
                "scopes" => ["Catalog"]
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        $key = $data[0]["value"];

        $response = $this->client->request("GET", "https://isl.dr-massive.com/api/account/items/$id/videos?delivery=stream&device=web_browser&ff=idp%2Cldp&lang=da&resolution=HD-1080&sub=Anonymous", [
            "headers" => [
                "x-authorization" => "Bearer $key"
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        $video = $data[0]["url"];
        $subtitles = $data[0]["subtitles"][0]["link"];

//        $response = $this->client->request("GET", "https://www.dr.dk/mu-online/api/1.4/programcard/?expanded=true&productionnumber=$id");
//        $data = json_decode($response->getBody(), true);
//        $video = $data["PrimaryAsset"]["Links"][1]["Uri"];
//        $subtitles = $data["PrimaryAsset"]["Subtitleslist"][0]["Uri"];
//
//        if ($video == null) {
//            $encrypted = $data["PrimaryAsset"]["Links"][1]["EncryptedUri"];
//            $n = hexdec(substr($encrypted, 2, 8));
//            $a = substr($encrypted, 10 + $n);
//            $data = hex2bin(substr($encrypted, 10, $n));
//            $key = hex2bin(hash("sha256",  $a . ":sRBzYNXBzkKgnjj8pGtkACch"));
//            $iv = hex2bin($a);
//
//            $video = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
//        }

        return new StreamInformation("da", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/dr\\.dk\\/drtv/i";
    }
}
