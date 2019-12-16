<?php


namespace app;


use Throwable;

class InvalidURLException extends \Exception
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("The URL did not match any of the supported broadcasters", $code, $previous);
    }
}