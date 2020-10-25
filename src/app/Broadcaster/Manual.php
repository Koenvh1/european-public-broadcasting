<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use Koenvh\PublicBroadcasting\StreamInformation;

class Manual extends Broadcaster
{

    function retrieve(string $url): StreamInformation
    {
        return new StreamInformation("manual", "");
    }

    static function getRegex(): string
    {
        return "/^manual$/i";
    }
}
