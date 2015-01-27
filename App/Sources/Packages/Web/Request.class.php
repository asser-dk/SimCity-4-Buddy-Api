<?php
class Request
{
    public static function GetPayload()
    {
        if (function_exists('http_get_request_body') === true)
        {
            return http_get_request_body();
        }

        return @file_get_contents('php://input');
    }
}
?>
