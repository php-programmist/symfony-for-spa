<?php


namespace App\Exception;


use Exception;
use Throwable;

class FrontendRoutingFailException extends Exception
{
    public function __construct($message = "Wrong Nginx routing", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}