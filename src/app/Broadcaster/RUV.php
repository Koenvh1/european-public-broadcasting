<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class RUV extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        $urlParts = explode("/", $url);
        $episodeId = end($urlParts);
        $programId = prev($urlParts);


        $response = $this->client->request("POST", "https://graphqladdi.spilari.ruv.is/", [
            "json" => [
                "query" => "{  Program(id: $programId) {    slug    title    description    foreign_title    id    image    portrait_image    episodes(limit: 1, id: {value: \"$episodeId\"}) {      title      id      description      firstrun      scope      rating      file_expires      file      clips {        time        text        slug        __typename      }      image      subtitles {        name        value        __typename      }      __typename    }    rest: episodes {      title      id      firstrun      description      image      __typename    }    __typename  }}"
            ]
        ]);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $video = $data["data"]["Program"]["episodes"][0]["file"];
        $subtitles = $data["data"]["Program"]["episodes"][0]["subtitles"][0]["value"];

        return new StreamInformation($video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/ruv\\.is/i";
    }
}
