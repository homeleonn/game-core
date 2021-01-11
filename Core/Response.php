<?php


namespace App\Core;


class Response
{
    public function getStatusCode(int $code)
    {
        http_response_code($code);
    }
}