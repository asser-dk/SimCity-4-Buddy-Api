<?php
    class BadRequestException extends HttpStatusException
    {
        public function __construct(integer $errorCode, string $message)
        {
            parent::__construct(400, 'Bad Request', $errorCode, $message);
        }
    }
?>
