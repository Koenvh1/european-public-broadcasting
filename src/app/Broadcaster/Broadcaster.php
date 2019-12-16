<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use app\InvalidURLException;
use Koenvh\PublicBroadcasting\StreamInformation;

abstract class Broadcaster
{
    protected $client;
    function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            "headers" => [
                "user-agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0",
                "origin" => "https://example.org",
            ]
        ]);
    }

    abstract static function getRegex() : string;
    abstract function retrieve(string $url) : StreamInformation;

    static function getStreamInformation(string $url) : StreamInformation
    {
        /** @var Broadcaster $broadcaster */
        $broadcaster = null;
        if (preg_match(ARD::getRegex(), $url)) {
            $broadcaster = new ARD();
        } elseif (preg_match(CeskaTelevize::getRegex(), $url)) {
            $broadcaster = new ARD();
        } elseif (preg_match(DR::getRegex(), $url)) {
            $broadcaster = new DR();
        } elseif (preg_match(ERR::getRegex(), $url)) {
            $broadcaster = new ERR();
        } elseif (preg_match(NPO::getRegex(), $url)) {
            $broadcaster = new NPO();
        } elseif (preg_match(NRK::getRegex(), $url)) {
            $broadcaster = new NRK();
        } elseif (preg_match(RTBF::getRegex(), $url)) {
            $broadcaster = new RTBF();
        } elseif (preg_match(RTS::getRegex(), $url)) {
            $broadcaster = new RTS();
        } elseif (preg_match(SVT::getRegex(), $url)) {
            $broadcaster = new SVT();
        } elseif (preg_match(TVP::getRegex(), $url)) {
            $broadcaster = new TVP();
        } elseif (preg_match(YLE::getRegex(), $url)) {
            $broadcaster = new YLE();
        } elseif (preg_match(ZDF::getRegex(), $url)) {
            $broadcaster = new ZDF();
        }

        if ($broadcaster == null) {
            throw new InvalidURLException();
        }

        return $broadcaster->retrieve($url);
    }
}